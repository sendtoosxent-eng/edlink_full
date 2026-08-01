<?php
namespace App\Http\Controllers;
use App\Models\{ContactMessage,PlatformAdmin,PlatformAuditLog,PlatformSetting,School};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Hash};
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class PlatformOperationsController extends Controller
{
 public function billing():View{$schools=School::orderByRaw('license_expires_at is null')->orderBy('license_expires_at')->get();return view('platform.operations.billing',compact('schools'));}
 public function audit(Request $r):View{$search=trim((string)$r->query('search'));$logs=PlatformAuditLog::with('administrator')->when($search,fn($q)=>$q->where('event','like','%'.$search.'%'))->latest()->paginate(30)->withQueryString();return view('platform.operations.audit',compact('logs','search'));}
 public function administrators():View{return view('platform.operations.administrators',['admins'=>PlatformAdmin::latest()->get()]);}
 public function storeAdministrator(Request $r):RedirectResponse{$d=$r->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:platform_admins','password'=>'required|string|min:12|confirmed','role'=>['required',Rule::in(['platform_owner','operations_admin','support_admin'])]]);$d['is_active']=true;$a=PlatformAdmin::create($d);$this->auditEvent($r,'platform.administrator.created',['administrator_id'=>$a->id,'email'=>$a->email]);return back()->with('status','Platform administrator added.');}
 public function updateAdministrator(Request $r,PlatformAdmin $platformAdmin):RedirectResponse{$d=$r->validate(['role'=>['required',Rule::in(['platform_owner','operations_admin','support_admin'])],'is_active'=>'nullable|boolean']);if($platformAdmin->is(Auth::guard('platform')->user())&&!$r->boolean('is_active'))return back()->withErrors(['administrator'=>'You cannot deactivate your own account.']);$platformAdmin->update(['role'=>$d['role'],'is_active'=>$r->boolean('is_active')]);$this->auditEvent($r,'platform.administrator.updated',['administrator_id'=>$platformAdmin->id]);return back()->with('status','Administrator updated.');}
 public function settings():View{$settings=array_merge(['support_email'=>'support@edlink.test','renewal_warning_days'=>'30','maintenance_message'=>''],PlatformSetting::values());$health=['Database'=>DB::connection()->getPdo()?'Healthy':'Unavailable','Storage'=>is_writable(storage_path())?'Writable':'Unavailable','Scheduler'=>file_exists(storage_path('framework/cache'))?'Ready':'Check required','Mail'=>config('mail.default')?:'Not configured'];return view('platform.operations.settings',compact('settings','health'));}
 public function updateSettings(Request $r):RedirectResponse{$d=$r->validate(['support_email'=>'required|email','renewal_warning_days'=>'required|integer|min:1|max:180','maintenance_message'=>'nullable|string|max:500']);foreach($d as $k=>$v)PlatformSetting::updateOrCreate(['key'=>$k],['value'=>$v]);$this->auditEvent($r,'platform.settings.updated',['fields'=>array_keys($d)]);return back()->with('status','Platform settings saved.');}
 private function auditEvent(Request $r,string $event,array $metadata):void{PlatformAuditLog::create(['platform_admin_id'=>Auth::guard('platform')->id(),'event'=>$event,'metadata'=>$metadata,'ip_address'=>$r->ip(),'user_agent'=>$r->userAgent()]);}
}
