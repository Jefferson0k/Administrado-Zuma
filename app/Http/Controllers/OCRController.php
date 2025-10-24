<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OCRController extends Controller{
    public function extractText(Request $request){
        $request->validate([
            'image' => 'required|image',
        ]);

        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return response()->json(['error' => 'Imagen inválida o no recibida.'], 400);
        }

        $destinationPath = public_path('images');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file = $request->file('image');
        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $fullPath = $destinationPath . '/' . $fileName;
        $file->move($destinationPath, $fileName);

        $response = Http::attach(
            'file', file_get_contents($fullPath), $fileName
        )->post('https://api.ocr.space/parse/image', [
            'apikey' => 'K88534373188957',
            'language' => 'spa',
        ]);

        $data = $response->json();
        $parsedText = $data['ParsedResults'][0]['ParsedText'] ?? 'No se pudo extraer texto.';

        $processedData = $this->processOCRText($parsedText);

        return response()->json([
            'text' => $parsedText,
            'file' => asset("images/{$fileName}"),
            'data' => $processedData
        ]);
    }
    
    
    private function processOCRText($text)
    {
        $data = [];

        $text = preg_replace('/\s+/', ' ', strtoupper($text));

        
        if (preg_match('/(?:NRO\.?\s*OPERACI[ÓO]N|NUMERO\s*DE\s*OPERACI[ÓO]N|OPE\.?|OPERACI[ÓO]N[:\s]*)[A-Z\s]*?(\d{3,})/i', $text, $matchOp)) {
            $numeroOperacion = $matchOp[1];
        } elseif (preg_match('/OPE\.?\s*(\d{3,})/i', $text, $matchOp)) {
            $numeroOperacion = $matchOp[1];
        } elseif (preg_match('/SOLES\s*(\d{3,})/i', $text, $matchOp)) {
            // Nuevo caso: "Soles1447388"
            $numeroOperacion = $matchOp[1];
        } else {
            $numeroOperacion = 'NO_DETECTADO';
        }

        preg_match_all('/\b\d{8}\b/', $text, $codigosMatches);
        $codigos = $codigosMatches[0] ?? [];

        preg_match_all('/(?:INGRESADO|IMPORTE|DEPOSITO|RECIBIDO|PAGO|MONTO)[^\d]{0,10}(\d{1,3}(?:[\s.,]\d{2})?)/i', $text, $montosKeywordMatches);
        $montos = $montosKeywordMatches[1] ?? [];

        if (empty($montos)) {
            preg_match_all('/\b\d{1,3}[\s.,]\d{2}\b/', $text, $montosGeneralMatches);
            $montos = $montosGeneralMatches[0] ?? [];
        }

        $montos = array_map(function ($monto) {
            $monto = trim(str_replace(',', '.', str_replace(' ', '', $monto)));
            if (!str_contains($monto, '.')) {
                $monto = substr_replace($monto, '.', -2, 0);
            }
            return number_format((float)$monto, 2, '.', '');
        }, $montos);

        $montos = array_filter($montos, function ($m) {
            return $m > 0 && $m < 100000;
        });

        $montos = array_values($montos);
        $codigos = array_values($codigos);

        $maxItems = min(count($codigos), count($montos)); 

        $data = [];

        for ($i = 0; $i < $maxItems; $i++) {
            $data[] = [
                'codigo2' => $codigos[$i],
                'monto' => number_format($montos[$i], 2, '.', ''),
                'codigo' => $numeroOperacion ?? 'NO_DETECTADO',
            ];
        }

        if (empty($data) && !empty($montos)) {
            $data[] = [
                'codigo2' => str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'monto' => number_format(max($montos), 2, '.', ''),
                'codigo' => $numeroOperacion ?? 'NO_DETECTADO',
            ];
        }

        return $data;
    }
    
}
