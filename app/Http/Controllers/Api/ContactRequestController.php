<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\ContactRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactToAdminMail;
use App\Mail\ContactToUserMail;
use App\Mail\ContactToSecondaryMail;

class ContactRequestController extends Controller {
    public function storeContactUs(StoreContactRequest $request) {
        $data = $request->validated();
        ContactRequest::create([
            ...$data,
            'status' => 'contact_us',
        ]);
        Mail::to('adminzuma@zuma.com.pe')->send(new ContactToAdminMail($data));
        Mail::to($data['email'])->send(new ContactToUserMail($data));
        Mail::to('jefersoncovenas7@gmail.com')->send(new ContactToSecondaryMail($data));
        return response()->json([
            'message' => 'Contact request received. We will get back to you soon.',
        ], 201);
    }
    public function storeInternal(StoreContactRequest $request) {
        $data = $request->validated();
        ContactRequest::create([
            ...$data,
            'status' => 'internal',
        ]);
        Mail::to('adminzuma@zuma.com.pe')->send(new ContactToAdminMail($data));
        Mail::to($data['email'])->send(new ContactToUserMail($data));
        Mail::to('jefersoncovenas7@gmail.com')->send(new ContactToSecondaryMail($data));
        return response()->json([
            'message' => 'Internal contact request saved successfully.',
        ], 201);
    }
}
