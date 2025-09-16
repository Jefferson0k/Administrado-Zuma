<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CorporateEntity\StoreCorporateEntityRequests;
use App\Http\Resources\Tasas\CorporateEntity\CorporateEntityResource;
use App\Models\CorporateEntity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class CorporateEntityController extends Controller{
    public function index()
    {
        $corporateEntities = CorporateEntity::latest()->get(); // DESC por created_at
        // si no tienes timestamps: CorporateEntity::orderByDesc('id')->get();

        return CorporateEntityResource::collection($corporateEntities);
    }
    public function store(StoreCorporateEntityRequests $request){
        $validated = $request->validated();
        if ($request->hasFile('pdf')) {
            $directory = storage_path('app/public/pdfs');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $pdf = $request->file('pdf');
            $randomName = Str::random(10) . '.pdf';
            if ($pdf->isValid()) {
                $pdf->move($directory, $randomName);
                $validated['pdf'] = $randomName;
            } else {
                return response()->json(['error' => 'Archivo no válido'], 422);
            }
        }
        $corporateEntity = CorporateEntity::create($validated);
        return new CorporateEntityResource($corporateEntity);
    }
    public function show($id){
        $entity = CorporateEntity::findOrFail($id);
        return new CorporateEntityResource($entity);
    }
    public function update(Request $request, $id){
        $entity = CorporateEntity::findOrFail($id);
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'ruc' => 'sometimes|string|max:20',
            'direccion' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'tipo_entidad' => 'sometimes|string|max:50',
            'estado' => 'sometimes|boolean',
            'pdf' => 'nullable|file|mimes:pdf|max:2048',
        ]);
        if ($request->hasFile('pdf')) {
            if ($entity->pdf) {
                $oldPath = storage_path('app/public/pdfs/' . $entity->pdf);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $directory = storage_path('app/public/pdfs');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }
            $pdf = $request->file('pdf');
            $randomName = Str::random(10) . '.pdf';
            if ($pdf->isValid()) {
                $pdf->move($directory, $randomName);
                $validated['pdf'] = $randomName;
            } else {
                return response()->json(['error' => 'Archivo no válido'], 422);
            }
        }
        $entity->update($validated);
        return new CorporateEntityResource($entity);
    }
    public function delete($id){
        $entity = CorporateEntity::findOrFail($id);
        if ($entity->pdf) {
            $pdfPath = storage_path('app/public/pdfs/' . $entity->pdf);
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }
        $entity->delete();
        return response()->json(['message' => 'Entidad eliminada correctamente']);
    }
    
    public function showPdf($id){
        $entity = CorporateEntity::findOrFail($id);

        if (!$entity->pdf) {
            return response()->json(['error' => 'Esta entidad no tiene un PDF asociado'], 404);
        }

        $path = storage_path('app/public/pdfs/' . $entity->pdf);

        if (!file_exists($path)) {
            return response()->json(['error' => 'El archivo PDF no se encuentra en el servidor'], 404);
        }

        return response()->stream(function () use ($path) {
            readfile($path);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
