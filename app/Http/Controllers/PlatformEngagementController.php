<?php
namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\PlatformAuditLog;
use App\Models\PublicEnquiry;
use App\Models\PublicVisit;
use App\Models\PublicVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformEngagementController extends Controller
{
    public function index(Request $request)
    {
        $status=(string)$request->query('status',''); $search=trim((string)$request->query('search',''));
        $enquiries=PublicEnquiry::with('visitor')->when(in_array($status,['new','contacted','demo_booked','joined','closed','do_not_contact']),fn($q)=>$q->where('status',$status))
            ->when($search,fn($q)=>$q->where(fn($s)=>$s->where('name','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%')->orWhere('school_name','like','%'.$search.'%')))->latest()->paginate(20)->withQueryString();
        $maySeeAddresses=in_array(Auth::guard('platform')->user()->role,['platform_owner','operations_admin']);
        return view('platform.engagement.index',[
            'enquiries'=>$enquiries,'visitors'=>$maySeeAddresses?PublicVisitor::withCount('visits')->with('enquiries:id,public_visitor_id,name')->latest('last_seen_at')->paginate(20,['*'],'visitors_page')->withQueryString():null,
            'maySeeAddresses'=>$maySeeAddresses,'status'=>$status,'search'=>$search,
            'counts'=>['visitors'=>PublicVisitor::where('last_seen_at','>=',now()->subDays(30))->count(),'views'=>PublicVisit::where('created_at','>=',now()->subDays(30))->count(),'enquiries'=>PublicEnquiry::count(),'new'=>PublicEnquiry::where('status','new')->count(),'help'=>PublicEnquiry::where('purpose','demo_help')->count(),'support'=>ContactMessage::whereIn('status',['new','open'])->count()],
        ]);
    }
    public function update(Request $request,PublicEnquiry $enquiry)
    {
        $data=$request->validate(['status'=>['required','in:new,contacted,demo_booked,joined,closed,do_not_contact'],'notes'=>['nullable','string','max:3000']]);
        $changes=$data+['closed_at'=>in_array($data['status'],['joined','closed','do_not_contact'])?now():null];
        if ($enquiry->opted_out_at && $data['status']!=='do_not_contact') return back()->withErrors(['status'=>'This person opted out. Do not reopen follow-up without a new request from them.']);
        if ($data['status']==='do_not_contact') $changes+=['followup_consent'=>false,'opted_out_at'=>now()];
        $enquiry->update($changes);
        PlatformAuditLog::create(['platform_admin_id'=>Auth::guard('platform')->id(),'event'=>'platform.enquiry.updated','metadata'=>['enquiry_id'=>$enquiry->id,'status'=>$data['status']],'ip_address'=>$request->ip()]);
        return back()->with('status','Enquiry updated. No message was sent.');
    }
}
