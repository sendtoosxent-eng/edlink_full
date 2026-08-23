<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\PlatformAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class PlatformSupportController extends Controller
{
    public function index(Request $request): View
    {
        $search=trim((string)$request->query('search')); $status=(string)$request->query('status');
        $messages=ContactMessage::query()->withCount('replies')
            ->when($search,fn($q)=>$q->where(fn($n)=>$n->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('subject','like',"%{$search}%")->orWhere('message','like',"%{$search}%")))
            ->when(in_array($status,['new','open','replied','closed'],true),fn($q)=>$q->where('status',$status))
            ->latest()->paginate(20)->withQueryString();
        return view('platform.support.index',['messages'=>$messages,'search'=>$search,'status'=>$status,'counts'=>ContactMessage::selectRaw('status, count(*) total')->groupBy('status')->pluck('total','status')]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        if(!$contactMessage->read_at)$contactMessage->update(['read_at'=>now(),'status'=>$contactMessage->status==='new'?'open':$contactMessage->status]);
        return view('platform.support.show',[
            'contactMessage'=>$contactMessage->load('replies.administrator'),
            'mailDeliveryEnabled'=>! in_array(config('mail.default'), ['log', 'array'], true),
            'mailDriver'=>config('mail.default'),
        ]);
    }

    public function reply(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $data=$request->validate(['subject'=>['required','string','max:255'],'message'=>['required','string','min:3','max:10000']]);
        $reply=$contactMessage->replies()->create(['platform_admin_id'=>Auth::guard('platform')->id(),'subject'=>$data['subject'],'message'=>$data['message'],'delivery_status'=>'pending']);
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $reply->update(['delivery_status'=>'failed','delivery_error'=>'Email delivery is disabled because MAIL_MAILER is set to '.config('mail.default').'.']);
            $this->audit($request,'platform.support.reply_failed',$contactMessage,['reply_id'=>$reply->id,'reason'=>'mail_delivery_disabled']);

            return back()->withErrors(['reply'=>'Email delivery is disabled. Configure MAIL_MAILER with a real email service (for example SMTP or Resend), then try again.']);
        }
        try {
            Mail::to($contactMessage->email,$contactMessage->name)->send(new ContactMessageReplyMail($contactMessage,$reply));
            $reply->update(['delivery_status'=>'sent','sent_at'=>now()]);
            $contactMessage->update(['status'=>'replied','read_at'=>$contactMessage->read_at?:now(),'closed_at'=>null]);
            $this->audit($request,'platform.support.replied',$contactMessage,['reply_id'=>$reply->id,'recipient'=>$contactMessage->email]);
            return back()->with('status','Reply sent to '.$contactMessage->email.'.');
        } catch(Throwable $exception) {
            report($exception); $reply->update(['delivery_status'=>'failed','delivery_error'=>str($exception->getMessage())->limit(1000)]);
            $this->audit($request,'platform.support.reply_failed',$contactMessage,['reply_id'=>$reply->id]);
            return back()->withErrors(['reply'=>'The reply could not be delivered. Check the mail configuration and try again.']);
        }
    }

    public function toggleStatus(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $closing=$contactMessage->status!=='closed';
        $contactMessage->update(['status'=>$closing?'closed':'open','closed_at'=>$closing?now():null,'read_at'=>$contactMessage->read_at?:now()]);
        $this->audit($request,$closing?'platform.support.closed':'platform.support.reopened',$contactMessage);
        return back()->with('status',$closing?'Conversation closed.':'Conversation reopened.');
    }

    private function audit(Request $request,string $event,ContactMessage $message,array $metadata=[]): void
    {
        PlatformAuditLog::create(['platform_admin_id'=>Auth::guard('platform')->id(),'event'=>$event,'metadata'=>array_merge(['contact_message_id'=>$message->id,'subject'=>$message->subject],$metadata),'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent()??'')->limit(500)->toString()?:null]);
    }
}
