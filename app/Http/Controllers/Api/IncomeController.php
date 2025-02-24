<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Requests\UpdateIncomeRequest;
use App\Http\Resources\IncomeResource;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user) {
            $incomes = Income::whereHas('company', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with('category', 'company')->latest()->paginate(10);
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return IncomeResource::collection($incomes);
    }

    public function store(StoreIncomeRequest $request)
    {
        $income = Income::create($request->validated());
        return new IncomeResource($income);
    }

    public function show(Income $income)
    {
        return new IncomeResource($income);
    }

    public function update(UpdateIncomeRequest $request, Income $income)
    {
        $data = $request->validated();
        $income->update($data);
        return new IncomeResource($income);
    }

    public function destroy(Income $income)
    {
        $income->delete();
        return response()->json(null, 204);
    }
}
