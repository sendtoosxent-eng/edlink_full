<?php
namespace App\Http\Controllers;

use App\Models\PublicEnquiry;
use App\Models\PublicVisit;
use App\Models\PublicVisitor;
use App\Services\SchoolWhatsapp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicEngagementController extends Controller
{
    private const PATHS=['/','/register','/login'];
    public function consent(Request $request)
    {
        $data=$request->validate(['analytics'=>['required','boolean']]);
        $id=$request->cookie('edlink_visitor');
        if (!$data['analytics']) {
            if (is_string($id) && Str::isUuid($id) && Schema::hasTable('public_visitors')) PublicVisitor::whereKey($id)->delete();
            return response()->json(['analytics'=>false])->cookie(cookie('edlink_visitor','',-1,'/',null,$request->isSecure(),true,false,'lax'))->cookie(cookie('edlink_public_consent','essential',60*24*180,'/',null,$request->isSecure(),true,false,'lax'));
        }
        abort_unless(Schema::hasTable('public_visitors'),503,'Visitor preferences are temporarily unavailable.');
        $id=is_string($id)&&Str::isUuid($id)?$id:(string)Str::uuid();
        PublicVisitor::firstOrCreate(['id'=>$id],['ip_address'=>$request->ip(),'browser'=>Str::limit((string)$request->userAgent(),250,''),'consent_version'=>'public-analytics-v1','consented_at'=>now(),'last_seen_at'=>now()]);
        return response()->json(['analytics'=>true])->cookie(cookie('edlink_visitor',$id,60*24*180,'/',null,$request->isSecure(),true,false,'lax'))->cookie(cookie('edlink_public_consent','analytics',60*24*180,'/',null,$request->isSecure(),true,false,'lax'));
    }
    public function visit(Request $request)
    {
        $data=$request->validate(['path'=>['required','in:'.implode(',',self::PATHS)],'event_key'=>['required','uuid'],'referrer'=>['nullable','string','max:1000']]);
        $id=$request->cookie('edlink_visitor');
        if ($request->cookie('edlink_public_consent')!=='analytics' || !is_string($id) || !Str::isUuid($id)) return response()->noContent();
        abort_unless(Schema::hasTable('public_visitors'),503);
        $host=parse_url($data['referrer']??'',PHP_URL_HOST);
        DB::transaction(function () use ($request,$id,$data,$host) {
            $visitor=PublicVisitor::firstOrCreate(['id'=>$id],['ip_address'=>$request->ip(),'browser'=>Str::limit((string)$request->userAgent(),250,''),'consent_version'=>'public-analytics-v1','consented_at'=>now(),'last_seen_at'=>now()]);
            $visit=PublicVisit::firstOrCreate(['event_key'=>$data['event_key']],['public_visitor_id'=>$visitor->id,'path'=>$data['path']]);
            if ($visit->wasRecentlyCreated) $visitor->update(['last_seen_at'=>now(),'ip_address'=>$request->ip(),'browser'=>Str::limit((string)$request->userAgent(),250,''),'referrer_host'=>$visitor->referrer_host?:($host?Str::limit($host,250,''):null)]);
        });
        return response()->noContent();
    }
    public function enquire(Request $request)
    {
        $data=$request->validate([
            'name'=>['required','string','max:255'],'email'=>['nullable','email','max:255'],'phone'=>['nullable','string','max:40'],
            'school_name'=>['nullable','string','max:255'],'school_location'=>['nullable','string','max:255'],
            'purpose'=>['required','in:demo_help,callback'],'source'=>['required','in:landing,demo_setup'],
            'contact_method'=>['required','in:email,whatsapp,phone'],'message'=>['nullable','string','max:3000'],
            'followup_consent'=>['required','accepted'],'fax_number'=>['nullable','string','max:255'],
        ]);
        if (filled($data['fax_number']??null)) return response()->json(['message'=>'Thank you. We’ll reach out soon.']);
        if ($data['contact_method']==='email' && blank($data['email']??'')) throw \Illuminate\Validation\ValidationException::withMessages(['email'=>'Enter your email address for an email follow-up.']);
        $phone=filled($data['phone']??'')?SchoolWhatsapp::phone($data['phone']):null;
        if (($data['contact_method']!=='email' || filled($data['phone']??'')) && !$phone) throw \Illuminate\Validation\ValidationException::withMessages(['phone'=>'Enter a valid phone number with its country code, for example +256772123456.']);
        abort_unless(Schema::hasTable('public_enquiries'),503,'Enquiries are temporarily unavailable. Please use our WhatsApp or email support links.');
        $visitor=null;
        if ($request->cookie('edlink_public_consent')==='analytics') $visitor=PublicVisitor::find($request->cookie('edlink_visitor'));
        PublicEnquiry::create(array_merge(collect($data)->except(['fax_number','followup_consent'])->all(),['phone'=>$phone,'public_visitor_id'=>$visitor?->id,'followup_consent'=>true,'consented_at'=>now(),'consent_version'=>PublicEnquiry::CONSENT_VERSION,'consent_text'=>PublicEnquiry::CONSENT_TEXT]));
        return response()->json(['message'=>'Thank you. Your request is with the Edlink support team. We’ll reach out soon.']);
    }
}
