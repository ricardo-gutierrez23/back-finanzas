<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    // CATEGORIES
    public function getCategories()
    {
        $result = DB::select("SELECT fn_get_categories() as items");
        return response()->json(json_decode($result[0]->items));
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:150', 'type' => 'required|in:Ingreso,Gasto']);
        $result = DB::select("SELECT fn_create_category(?, ?) as response", [$request->name, $request->type]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:150', 'type' => 'required|in:Ingreso,Gasto']);
        $result = DB::select("SELECT fn_update_category(?, ?, ?) as response", [$id, $request->name, $request->type]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }

    public function deleteCategory($id)
    {
        $result = DB::select("SELECT fn_delete_category(?) as response", [$id]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }

    // PAYMENT METHODS
    public function getPaymentMethods()
    {
        $result = DB::select("SELECT fn_get_payment_methods() as items");
        return response()->json(json_decode($result[0]->items));
    }

    public function storePaymentMethod(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $result = DB::select("SELECT fn_create_payment_method(?) as response", [$request->name]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 201);
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $result = DB::select("SELECT fn_update_payment_method(?, ?) as response", [$id, $request->name]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }

    public function deletePaymentMethod($id)
    {
        $result = DB::select("SELECT fn_delete_payment_method(?) as response", [$id]);
        $response = json_decode($result[0]->response);
        return response()->json($response, isset($response->status) && $response->status === 'error' ? 400 : 200);
    }
}
