<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class OCRDniController extends Controller
{
    public function extractText(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
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

        //$url_api = 'https://api.ocr.space/parse/image';
        $url_api = 'http://api.ocr.space/parse/image';
        
        // Enviar a OCR
        $response = Http::attach(
            'file',
            file_get_contents($fullPath),
            $fileName
        )->post($url_api, [
            'apikey' => 'K88534373188957',
            'language' => 'spa',
        ]);

        $data = $response->json();
        $parsedText = $data['ParsedResults'][0]['ParsedText'] ?? '';

        // Extraer DNI del texto
        preg_match('/\b(\d{7,8})-\d\b/', $parsedText, $match);
        $dni = $match[1] ?? null;

        if (!$dni) {
            preg_match('/DN\s?(\d{8,10})/i', $parsedText, $matchAlt);
            $dni = isset($matchAlt[1]) ? substr($matchAlt[1], -8) : null;
        }

        // Validar contra el usuario autenticado
        $investor = Auth::user(); // Si usas el guard por defecto
        // Si tienes un guard específico para investors, usa:
        // $investor = Auth::guard('investor')->user();
        
        $validationResult = [
            'is_valid' => false,
            'message' => ''
        ];

        if (!$dni) {
            $validationResult['message'] = 'No se pudo extraer el número de DNI de la imagen. Por favor, toma una foto más clara.';
        } elseif (!$investor) {
            $validationResult['message'] = 'Usuario no autenticado.';
        } elseif ($dni !== $investor->document) {
            $validationResult['message'] = 'El número de DNI de la imagen no coincide con el registrado en tu cuenta.';
        } else {
            $validationResult['is_valid'] = true;
            $validationResult['message'] = 'DNI validado correctamente.';
        }

        return response()->json([
            'dni' => $dni,
            'text' => $parsedText,
            'file' => asset("images/{$fileName}"),
            'validation' => $validationResult
        ]);
    }
}