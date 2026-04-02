<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function getSummary(Request $request)
    {
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));
        
        $result = DB::select("SELECT fn_get_user_summary(?::integer, ?::integer, ?::integer) as summary", [
            $request->user()->id, (int)$month, (int)$year
        ]);
        
        return response()->json(json_decode($result[0]->summary));
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $transactions = DB::select('
            SELECT t.*, c.name as category_name, c.type as type, pm.name as payment_method_name 
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            JOIN payment_methods pm ON t.payment_method_id = pm.id
            WHERE t.user_id = ? 
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT 50
        ', [$userId]);
        
        return response()->json($transactions);
    }

    public function getYearlyTrend(Request $request)
    {
        $year = $request->query('year', date('Y'));
        
        $result = DB::select("SELECT fn_get_yearly_trend(?, ?) as trend", [
            $request->user()->id, $year
        ]);
        
        return response()->json(json_decode($result[0]->trend));
    }

    public function getTransactions(Request $request)
    {
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));
        
        $result = DB::select("SELECT fn_get_transactions(?, ?, ?) as transactions", [
            $request->user()->id, $month, $year
        ]);
        
        return response()->json(json_decode($result[0]->transactions));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer',
            'payment_method_id' => 'required|integer',
            'amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'observations' => 'nullable|string'
        ]);

        $result = DB::select("SELECT fn_create_transaction(?, ?, ?, ?, ?, ?, ?) as response", [
            $request->user()->id,
            $data['category_id'],
            $data['payment_method_id'],
            $data['amount'],
            $data['transaction_date'],
            $data['description'] ?? '',
            $data['observations'] ?? ''
        ]);

        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'category_id' => 'required|integer',
            'payment_method_id' => 'required|integer',
            'amount' => 'required|numeric',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'observations' => 'nullable|string'
        ]);

        $result = DB::select("SELECT fn_update_transaction(?, ?, ?, ?, ?, ?, ?, ?) as response", [
            $id,
            $request->user()->id,
            $data['category_id'],
            $data['payment_method_id'],
            $data['amount'],
            $data['transaction_date'],
            $data['description'] ?? '',
            $data['observations'] ?? ''
        ]);

        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }

    public function destroy(Request $request, $id)
    {
        $result = DB::select("SELECT fn_delete_transaction(?, ?) as response", [
            $id, $request->user()->id
        ]);

        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }
}
