<?php

namespace App\Http\Controllers;

final class FilterController extends Controller
{
    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('filter');
    }

    public function search(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('search');
    }

    public function clear(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $filterType = $request->input('filter_type', 'selected_emojis');

        $user = auth()->user();
        $user->$filterType = [];
        $user->save();

        return redirect()->back()->with('success', 'Filter cleared successfully');
    }

    public function clearSearch(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        $user->search_query = null;
        $user->search_query_only = false;
        $user->save();

        return redirect()->back()->with('success', 'Search cleared successfully');
    }
}
