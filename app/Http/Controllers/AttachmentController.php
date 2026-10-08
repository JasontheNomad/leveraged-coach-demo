<?php

namespace App\Http\Controllers;

use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(MessageAttachment $attachment)
    {
        abort_unless(
            $attachment->message->conversation->participants->contains(auth()->id()),
            403
        );

        return redirect(
            Storage::disk('r2-private')->temporaryUrl($attachment->path, now()->addMinutes(5))
        );
    }
}
