<?php
// app/Http/Controllers/WalkinSaleController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use App\Helpers\NotificationHelper;

class WalkinSaleController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────────────────────
    // GET /staff/walkin
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $products = DB::table('products')
            ->where('status', 'available')
            ->where('quantity', '>', 0)
            ->orderBy('product_name')
            ->get();

        $services = DB::table('services')
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $patients = DB::table('users')
            ->where('role', 'patient')
            ->orderBy('firstName')
            ->get();

        $doctors = DB::table('users')
            ->where('role', 'doctor')
            ->orderBy('firstName')
            ->get();

        $recentSales = DB::table('walkin_sales as s')
            ->join('users as p',  'p.user_id',  '=', 's.user_id')
            ->join('users as st', 'st.user_id', '=', 's.staff_id')
            ->select(
                's.*',
                DB::raw('CONCAT(p.firstName, " ", p.lastName) as patient_name'),
                DB::raw('CONCAT(st.firstName, " ", st.lastName) as staff_name')
            )
            ->orderByDesc('s.created_at')
            ->get();

        // ── Pre-fill service from completed appointment ────────
        // StaffBookingsController redirects here with
        // ?from_appointment=X&patient_id=Y after marking completed.
        // We fetch the appointment's service so the blade can
        // auto-populate the Service Add-ons section.
        $prefillService = null;
        $fromAppointmentId = $request->query('from_appointment');

        if ($fromAppointmentId) {
            $appt = DB::table('appointments as a')
                ->leftJoin('services as s',  's.service_id',  '=', 'a.service_id')
                ->leftJoin('doctor as dc',   'dc.doctor_id',  '=', 'a.doctor_id')
                ->leftJoin('users as d',     'd.user_id',     '=', 'dc.user_id')
                ->select(
                    'a.appointment_id',
                    'a.service_id',
                    'a.appointment_date',
                    'a.appointment_time',
                    'dc.user_id  as doctor_user_id',   // matches DOCTORS JS array
                    's.name      as service_name',
                    's.price     as service_price',
                    DB::raw('CONCAT(d.firstName, " ", d.lastName) as doctor_name')
                )
                ->where('a.appointment_id', $fromAppointmentId)
                ->first();

            if ($appt && $appt->service_id) {
                $prefillService = [
                    'service_id'       => $appt->service_id,
                    'service_name'     => $appt->service_name,
                    'service_price'    => (float) $appt->service_price,
                    'doctor_user_id'   => $appt->doctor_user_id,
                    'doctor_name'      => $appt->doctor_name,
                    'appointment_date' => $appt->appointment_date,
                    'appointment_time' => $appt->appointment_time,
                    'appointment_id'   => $fromAppointmentId,
                ];
            }
        }

        return view('staff_walkin', array_merge(
            $this->sidebarData(),
            compact('products', 'services', 'patients', 'doctors', 'recentSales', 'prefillService')
        ));
    }

    // ─────────────────────────────────────────────────────────
    // POST /staff/walkin/store
    // ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'user_id'                     => 'required|integer|exists:users,user_id',
            'payment_method'              => 'required|in:cash,gcash',
            'amount_tendered'             => 'nullable|numeric|min:0',
            'notes'                       => 'nullable|string|max:500',
            'items'                       => 'nullable|array',
            'items.*.product_id'          => 'required_with:items|integer|exists:products,product_id',
            'items.*.quantity'            => 'required_with:items|integer|min:1',
            'services'                    => 'nullable|array',
            'services.*.service_id'       => 'required_with:services|integer|exists:services,service_id',
            'services.*.doctor_id'        => 'required_with:services|integer|exists:users,user_id',
            'services.*.appointment_date' => 'required_with:services|date|after_or_equal:today',
            'services.*.appointment_time' => 'required_with:services|date_format:H:i',
            'services.*.existing_appointment_id' => 'nullable|integer',
        ]);

        $hasItems    = !empty($request->items);
        $hasServices = !empty($request->services);
        if (!$hasItems && !$hasServices) {
            return back()->withInput()->with('error', 'Add at least one product or service.');
        }

        DB::beginTransaction();
        try {
            $staffId  = session('user_id');
            $subtotal = 0;

            // ── 1. Validate stock & build product rows ──
            $itemRows = [];
            if ($hasItems) {
                foreach ($request->items as $item) {
                    $product = DB::table('products')
                        ->where('product_id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$product) throw new \Exception("Product ID {$item['product_id']} not found.");
                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Not enough stock for \"{$product->product_name}\". Available: {$product->quantity}.");
                    }

                    $lineTotal  = $product->selling_price * $item['quantity'];
                    $subtotal  += $lineTotal;
                    $itemRows[] = ['product' => $product, 'quantity' => $item['quantity'], 'lineTotal' => $lineTotal];
                }
            }

            // ── 2. Build service rows ──
            $serviceRows = [];
            if ($hasServices) {
                foreach ($request->services as $svc) {
                    $service = DB::table('services')->where('service_id', $svc['service_id'])->first();
                    if (!$service) throw new \Exception("Service ID {$svc['service_id']} not found.");

                    $doctorRow = DB::table('doctor')->where('user_id', $svc['doctor_id'])->first();
                    if (!$doctorRow) throw new \Exception('Doctor profile not found for the selected doctor.');

                    // Only check for conflicts if this is a NEW service booking
                    // (not the already-completed appointment being billed)
                    $isExisting = !empty($svc['existing_appointment_id']);
                    if (!$isExisting) {
                        $conflict = DB::table('appointments')
                            ->where('doctor_id',        $doctorRow->doctor_id)
                            ->where('appointment_date', $svc['appointment_date'])
                            ->where('appointment_time', $svc['appointment_time'])
                            ->whereNotIn('status', ['cancelled'])
                            ->exists();

                        if ($conflict) {
                            $du = DB::table('users')->where('user_id', $svc['doctor_id'])->first();
                            throw new \Exception("Time slot {$svc['appointment_date']} {$svc['appointment_time']} is already taken for Dr. {$du->firstName} {$du->lastName}.");
                        }
                    }

                    $price       = $service->price ?? 0;
                    $subtotal   += $price;
                    $serviceRows[] = [
                        'service'                => $service,
                        'doctor_id'              => $doctorRow->doctor_id,
                        'appointment_date'       => $svc['appointment_date'],
                        'appointment_time'       => $svc['appointment_time'],
                        'price'                  => $price,
                        'existing_appointment_id'=> $svc['existing_appointment_id'] ?? null,
                    ];
                }
            }

            // ── 3. Create sale record ──
            $saleId = DB::table('walkin_sales')->insertGetId([
                'user_id'         => $request->user_id,
                'staff_id'        => $staffId,
                'subtotal'        => $subtotal,
                'total_amount'    => $subtotal,
                'payment_method'  => $request->payment_method,
                'amount_tendered' => $request->amount_tendered,
                'status'          => 'completed',
                'notes'           => $request->notes,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // ── 4. Product items + FIFO deduction ──
            foreach ($itemRows as $row) {
                DB::table('walkin_sale_items')->insert([
                    'sale_id'    => $saleId,
                    'product_id' => $row['product']->product_id,
                    'quantity'   => $row['quantity'],
                    'unit_price' => $row['product']->selling_price,
                    'subtotal'   => $row['lineTotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $remaining = $row['quantity'];
                $batches = DB::table('inventory_logs')
                    ->where('product_id', $row['product']->product_id)
                    ->where('type', 'IN')->where('quantity', '>', 0)
                    ->orderBy('expiry_date')->orderBy('id')->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    if ($batch->quantity >= $remaining) {
                        DB::table('inventory_logs')->where('id', $batch->id)->decrement('quantity', $remaining);
                        $remaining = 0;
                    } else {
                        DB::table('inventory_logs')->where('id', $batch->id)->update(['quantity' => 0]);
                        $remaining -= $batch->quantity;
                    }
                }

                $newQty = DB::table('inventory_logs')
                    ->where('product_id', $row['product']->product_id)
                    ->where('type', 'IN')->where('quantity', '>', 0)->sum('quantity');

                DB::table('products')->where('product_id', $row['product']->product_id)->update(['quantity' => $newQty]);
                $this->notifyStockLevel($row['product'], $newQty);
            }

            // ── 5. Book service add-ons ──
            foreach ($serviceRows as $row) {
                // If this service came from an already-completed appointment,
                // just link it to the sale — don't create a duplicate appointment
                if (!empty($row['existing_appointment_id'])) {
                    DB::table('walkin_sale_services')->insert([
                        'sale_id'        => $saleId,
                        'appointment_id' => $row['existing_appointment_id'],
                        'service_price'  => $row['price'],
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                    continue; // skip creating a new appointment
                }

                // New service — create appointment as before
                $appointmentId = DB::table('appointments')->insertGetId([
                    'user_id'          => $request->user_id,
                    'doctor_id'        => $row['doctor_id'],
                    'service_id'       => $row['service']->service_id,
                    'appointment_date' => $row['appointment_date'],
                    'appointment_time' => $row['appointment_time'],
                    'status'           => 'approved',
                    'cancel_reason'    => null,
                    'is_rescheduled'   => 0,
                    'notes'            => 'Walk-in add-on via sale #' . $saleId,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                DB::table('walkin_sale_services')->insert([
                    'sale_id'        => $saleId,
                    'appointment_id' => $appointmentId,
                    'service_price'  => $row['price'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('staff.walkin.receipt', ['id' => $saleId])
                ->with('success', 'Sale completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────
    // GET /staff/walkin/receipt/{id}
    // ─────────────────────────────────────────────────────────
    public function receipt($id)
    {
        $sale = DB::table('walkin_sales as s')
            ->join('users as p',  'p.user_id',  '=', 's.user_id')
            ->join('users as st', 'st.user_id', '=', 's.staff_id')
            ->select('s.*',
                DB::raw('CONCAT(p.firstName, " ", p.lastName) as patient_name'),
                'p.email as patient_email',
                DB::raw('CONCAT(st.firstName, " ", st.lastName) as staff_name'))
            ->where('s.sale_id', $id)->first();

        abort_if(!$sale, 404);

        $productItems = DB::table('walkin_sale_items as i')
            ->join('products as p', 'p.product_id', '=', 'i.product_id')
            ->select('i.*', 'p.product_name', 'p.image')
            ->where('i.sale_id', $id)->get();

        $serviceItems = DB::table('walkin_sale_services as ss')
            ->join('appointments as a',  'a.appointment_id', '=', 'ss.appointment_id')
            ->join('services as sv',     'sv.service_id',    '=', 'a.service_id')
            ->join('doctor as dc',       'dc.doctor_id',     '=', 'a.doctor_id')
            ->join('users as d',         'd.user_id',        '=', 'dc.user_id')
            ->select('ss.*', 'sv.name as service_name', 'a.appointment_date',
                'a.appointment_time', DB::raw('CONCAT(d.firstName, " ", d.lastName) as doctor_name'))
            ->where('ss.sale_id', $id)->get();

        $change = null;
        if ($sale->payment_method === 'cash' && $sale->amount_tendered !== null) {
            $change = $sale->amount_tendered - $sale->total_amount;
        }

        return view('staff_walkin_receipt', array_merge(
            $this->sidebarData(),
            compact('sale', 'productItems', 'serviceItems', 'change')
        ));
    }

    // ─────────────────────────────────────────────────────────
    // AJAX GET /staff/walkin/check-slot
    // ─────────────────────────────────────────────────────────
    public function checkSlot(Request $request)
    {
        $doctorRow = DB::table('doctor')->where('user_id', $request->doctor_id)->first();
        if (!$doctorRow) return response()->json(['available' => false]);

        $taken = DB::table('appointments')
            ->where('doctor_id',        $doctorRow->doctor_id)
            ->where('appointment_date', $request->date)
            ->where('appointment_time', $request->time)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        return response()->json(['available' => !$taken]);
    }

    private function notifyStockLevel($product, int $newQty): void
    {
        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        if ($newQty > 0 && $newQty <= $product->reorder_level) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send($u->user_id, '⚠ Low Stock Warning',
                    "{$product->product_name} is low on stock ({$newQty} units remaining).", 'inventory');
            }
        }
        if ($newQty === 0) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send($u->user_id, '❌ Out of Stock',
                    "{$product->product_name} is now out of stock.", 'inventory');
            }
        }
    }
}