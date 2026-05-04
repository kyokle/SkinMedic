<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class NotificationController extends Controller
{
    private function userId() { return Session::get('user_id'); }
    private function check()  { return Session::has('user_id'); }

    // ── General (used by old bell, keep for backward compat) ──
    public function unreadCount()
    {
        if (!$this->check()) return response()->json(['count' => 0]);
        $count = DB::table('notifications')
            ->where('user_id', $this->userId())
            ->where('is_read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    public function index()
    {
        if (!$this->check()) return response()->json([]);
        return response()->json(
            DB::table('notifications')
                ->where('user_id', $this->userId())
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function markRead(Request $request)
    {
        if (!$this->check()) return response()->json(['success' => false]);
        DB::table('notifications')
            ->where('id', $request->id)
            ->where('user_id', $this->userId())
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        if (!$this->check()) return response()->json(['success' => false]);
        DB::table('notifications')
            ->where('user_id', $this->userId())
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    // ── Inventory (existing) ──────────────────────────────────
    private array $inventoryTitles = [
        'Stock Added', 'Stock Deducted', '⚠ Low Stock Warning', '❌ Out of Stock', '⏰ Expiry Warning'
    ];

    public function inventoryIndex()
    {
        if (!$this->check()) return response()->json([]);
        return response()->json(
            DB::table('notifications')
                ->where('user_id', $this->userId())
                ->whereIn('title', $this->inventoryTitles)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function unreadInventory()
    {
        if (!$this->check()) return response()->json(['count' => 0]);
        $count = DB::table('notifications')
            ->where('user_id', $this->userId())
            ->whereIn('title', $this->inventoryTitles)
            ->where('is_read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    public function markAllInventoryRead()
    {
        if (!$this->check()) return response()->json(['success' => false]);
        DB::table('notifications')
            ->where('user_id', $this->userId())
            ->whereIn('title', $this->inventoryTitles)
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    // ── By type (new) ─────────────────────────────────────────
    public function byType(Request $request)
    {
        if (!$this->check()) return response()->json([]);
        $type = $request->query('type', 'all');

        $query = DB::table('notifications')
            ->where('user_id', $this->userId());

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    public function unreadByType(Request $request)
    {
        if (!$this->check()) return response()->json(['count' => 0]);
        $type = $request->query('type', 'all');

        $query = DB::table('notifications')
            ->where('user_id', $this->userId())
            ->where('is_read', false);

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        return response()->json(['count' => $query->count()]);
    }

    public function markAllByTypeRead(Request $request)
    {
        if (!$this->check()) return response()->json(['success' => false]);
        $type = $request->query('type', 'all');

        $query = DB::table('notifications')
            ->where('user_id', $this->userId());

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $query->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}