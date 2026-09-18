<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChatbotMetricasService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotMetricasController extends Controller
{
    public function index(Request $request, ChatbotMetricasService $metricasService): View
    {
        $periodo = $request->string('periodo')->toString();
        $periodo = array_key_exists($periodo, ChatbotMetricasService::PERIODOS) ? $periodo : '30';
        $metricas = $metricasService->panel($periodo);
        $periodos = ChatbotMetricasService::PERIODOS;

        return view('admin.chatbot.metricas', compact('metricas', 'periodos', 'periodo'));
    }
}
