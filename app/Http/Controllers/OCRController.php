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
    private function processOCRText($text){
        $data = [];

        preg_match_all('/\b\d{8}\b/', $text, $codigosMatches);
        $codigos = $codigosMatches[0] ?? [];
        preg_match_all('/[Ss]\/\s*\d+(?:\.\d{2})?/', $text, $montosMatches);
        $montos = $montosMatches[0] ?? [];

        $maxItems = max(count($codigos), count($montos));
        
        for ($i = 0; $i < $maxItems; $i++) {
            $item = [];

            $item['codigo'] = $codigos[$i] ?? str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $item['monto'] = isset($montos[$i])
                ? preg_replace('/[^\d.]/', '', $montos[$i])
                : '0.00';

            $data[] = $item;
        }

        return $data;
    }
}
