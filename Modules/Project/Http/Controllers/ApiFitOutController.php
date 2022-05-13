<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateDesainRequest;
use Modules\Project\Http\Requests\Project\CreateFitOutRequest;
use Modules\Project\Http\Requests\Project\DeleteDesain;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use App\Lib\Icount;
use Modules\Project\Entities\ProjectSurveyLocation;
use Modules\Project\Entities\ProjectFitOut;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use Modules\Project\Entities\InvoiceBap;

class ApiFitOutController extends Controller
{
   public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/fit_out/"; 
    }
    public function create(CreateFitOutRequest $request)
    {
        $attachment = null;
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        $store = ProjectFitOut::where(array('id_project'=>$request->id_project))->first();
        if($store){
            $attachment = $store->attachment;
            $note = $store->note;
            if(isset($request->note)){
                $note = $request->note;
            }
            if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, 'pdf');
                     if (isset($upload['status']) && $upload['status'] == "success") {
                             $attachment = $upload['path'];
                         } else {
                             $result = [
                                 'status'   => 'fail',
                                 'messages' => ['fail upload file']
                             ];
                             return $result;
                         }
                 }
            $store = ProjectFitOut::create([
                    "id_project"   =>  $request->id_project,
                    "title"   =>   $request->title,
                    "progres"   =>   $request->progres,
                    "attachment"   =>  $attachment,
                    "note"   =>  $note
                ]);
        }else{
            if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, 'pdf');
                     if (isset($upload['status']) && $upload['status'] == "success") {
                             $attachment = $upload['path'];
                         } else {
                             $result = [
                                 'status'   => 'fail',
                                 'messages' => ['fail upload file']
                             ];
                             return $result;
                         }
                 }
            $store = ProjectFitOut::create([
                    "id_project"   =>  $request->id_project,
                    "title"   =>   $request->title,
                    "progres"   =>   $request->progres,
                    "attachment"   =>  $attachment,
                    "note"   =>  $note
                ]);
        }
            return response()->json(MyHelper::checkCreate($store));
    }
    
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Fit Out"))
                ->first();
         if($project){
             $project->progres = 'Handover';
             
             $data_send = [
                            "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                            "location" => Location::where('id_location',$project->id_location)->first(),
                            "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),
                        ];
       $invoice = Icount::ApiInvoiceBAP($data_send,'PT IMA');
            if($invoice['response']['Status']=='1' && $invoice['response']['Message']=='success'){
             $data_invoice = [
                 'id_project'=>$request->id_project,
                 'id_sales_invoice'=>$invoice['response']['Data'][0]['SalesInvoiceID'],
                 'id_business_partner'=>$invoice['response']['Data'][0]['BusinessPartnerID'],
                 'id_branch'=>$invoice['response']['Data'][0]['BranchID'],
                 'dpp'=>$invoice['response']['Data'][0]['DPP'],
                 'dpp_tax'=>$invoice['response']['Data'][0]['DPPTax'],
                 'tax'=>$invoice['response']['Data'][0]['Tax'],
                 'tax_value'=>$invoice['response']['Data'][0]['TaxValue'],
                 'tax_date'=>date('Y-m-d H:i:s',strtotime($invoice['response']['Data'][0]['TaxDate'])),
                 'netto'=>$invoice['response']['Data'][0]['Netto'],
                 'amount'=>$invoice['response']['Data'][0]['Amount'],
                 'outstanding'=>$invoice['response']['Data'][0]['Outstanding'],
                 'value_detail'=>json_encode($invoice['response']['Data'][0]['Detail']),  
                  'message'=>$invoice['response']['Message']
             ];
              $input = InvoiceBap::create($data_invoice);
           }else{
               $data_invoice = [
                 'id_project'=>$request->id_project,
                 'status_invoice_bap'=>0,
                 'message'=>$purchase['response']['message'],
                 'value_detail'=>json_encode($invoice['response']['Data']),  
             ];
              $input = InvoiceBap::create($data_invoice);   
           }
        $fitOut = ProjectFitOut::where(array('id_project'=>$request->id_project,'status'=>'Process'))->get();
        foreach ($fitOut as $value) {
            $value['status'] = "Success";
            $value->save();
        }
        $project->save();
         $project = Project::where(array('id_project'=>$request->id_project))->join('partners','partners.id_partner','projects.id_partner')->first();
            if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Update Project',
                            $project->phone,
                            [
                                'name' => $project->name,
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => 'Tidak dalam proses fit out']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => 'Incompleted Data']);
        }
    }
    public function destroy(Request $request)
    {
        if(isset($request->id_projects_fit_out)){
        $delete = ProjectFitOut::where('id_projects_fit_out', $request->id_projects_fit_out)->where(array('status'=>'Process'))->delete();
        return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function invoice_bap(Request $request)
    {
        if(isset($request->id_project)){
        $project = Project::where('id_project', $request->id_project)->first();
         if($project){
             $data_send = [
                            "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                            "location" => Location::where('id_location',$project->id_location)->first(),
                            "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),
                        ];
       $invoice = Icount::ApiInvoiceBAP($data_send,'PT IMA');
            if($invoice['response']['Status']=='1' && $invoice['response']['Message']=='success'){
             $data_invoice = [
                 'id_sales_invoice'=>$invoice['response']['Data'][0]['SalesInvoiceID'],
                 'id_business_partner'=>$invoice['response']['Data'][0]['BusinessPartnerID'],
                 'id_branch'=>$invoice['response']['Data'][0]['BranchID'],
                 'dpp'=>$invoice['response']['Data'][0]['DPP'],
                 'dpp_tax'=>$invoice['response']['Data'][0]['DPPTax'],
                 'tax'=>$invoice['response']['Data'][0]['Tax'],
                 'tax_value'=>$invoice['response']['Data'][0]['TaxValue'],
                 'tax_date'=>date('Y-m-d H:i:s',strtotime($invoice['response']['Data'][0]['TaxDate'])),
                 'netto'=>$invoice['response']['Data'][0]['Netto'],
                 'amount'=>$invoice['response']['Data'][0]['Amount'],
                 'outstanding'=>$invoice['response']['Data'][0]['Outstanding'],
                 'value_detail'=>json_encode($invoice['response']['Data'][0]['Detail']),  
                 'status_invoice_bap'=>1,
                 'message'=>$invoice['response']['Message'],
             ];
              $input = InvoiceBap::where(array('id_project'=>$request->id_project))->update($data_invoice);
           }else{
               $data_invoice = [
                 'status_invoice_bap'=>0,
                 'message'=>$invoice['response']['Message'],
                 'value_detail'=>json_encode($invoice['response']['Data']),  
             ];
              $input = InvoiceBap::where(array('id_project'=>$request->id_project))->update($data_invoice);   
              return response()->json(['status' => 'fail','messages'=>$invoice['response']['Message']]);
           }
        
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => 'Tidak dalam proses fit out']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    
    }
    public function index(Request $request)
    {
        if(isset($request->id_project)){
        $index = ProjectFitOut::where('id_project', $request->id_project)->get();
         return response()->json(['status' => 'success','result'=>$index]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
