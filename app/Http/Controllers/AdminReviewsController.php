<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;

class AdminReviewsController extends Controller
{
    use SidebarDataController;

    public function index()
    {
        $reviews = DB::table('reviews as r')
            ->leftJoin('users as u', 'r.user_id', '=', 'u.user_id')
            ->leftJoin('services as s', 'r.service_id', '=', 's.service_id')
            ->select(
                'r.review_id',
                'r.rating',
                'r.comment',
                'r.created_at',
                's.name as service_name',
                DB::raw("CONCAT(u.firstName, ' ', u.lastName) AS patient_name")
            )
            ->orderByDesc('r.created_at')
            ->get();

        $total     = $reviews->count();
        $avgRating = $total > 0 ? round($reviews->avg('rating'), 1) : 0;

        return view('admin_reviews', array_merge(
            $this->sidebarData(),
            compact('reviews', 'total', 'avgRating')
        ));
    }

    public function destroy($id)
    {
        $deleted = DB::table('reviews')->where('review_id', $id)->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => (bool) $deleted]);
        }

        return redirect()->route('admin.reviews');
    }
}