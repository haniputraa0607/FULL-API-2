<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateContractRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use App\Lib\Icount;
use Modules\Project\Entities\ProjectContract;
use Modules\Project\Entities\ProjectSurveyLocation;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\LocationOutletStarterBundlingProduct;
use Modules\BusinessDevelopment\Entities\ConfirmationLetter;
use App\Http\Models\Outlet;
use Modules\Project\Entities\InvoiceSpk;
use Modules\Project\Entities\PurchaseSpk;

class ApiContractController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/contract/"; 
    }
    public function create(CreateContractRequest $request)
    {
        $attachment = null;
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        if(isset($request->id_project)){
            $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Contract"))
            ->first();
            if($project){
                $data_send = [
                    "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                    "location" => Location::where('id_location',$project->id_location)->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),  
                    "location_bundling" => LocationOutletStarterBundlingProduct::where('id_location',$project->id_location)->join('product_icounts','product_icounts.id_product_icount','location_outlet_starter_bundling_products.id_product_icount')->get(),
                ];
                $invoice = Icount::ApiInvoiceSPK($data_send,'PT IMA');
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
                        'message'=>$invoice['response']['Message'],
                    ];
                    $input = InvoiceSpk::create($data_invoice);
                }else{
                    $data_invoice = [
                        'id_project'=>$request->id_project,
                        'status_invoice_spk'=>0,
                        'message'=>$invoice['response']['Message'],
                        'value_detail'=>json_encode($invoice['response']['Data']),  
                    ];
                    $input = InvoiceSpk::create($data_invoice);
                }
                $purchase = Icount::ApiPurchaseSPK($data_send,'PT IMA');
                if($purchase['response']['Status']=='1' && $purchase['response']['Message']=='success'){
                    $value_detail = array_map(function($value){
                        unset($value['Item']['ItemImage']);
                        return $value;
                    }, $purchase['response']['Data'][0]['Detail'] ?? []);
                    $data_purchase = [
                        'id_project'=>$request->id_project,
                        'id_request_purchase'=>$purchase['response']['Data'][0]['PurchaseRequestID'],
                        'id_business_partner'=>$purchase['response']['Data'][0]['BusinessPartnerID'],
                        'id_branch'=>$purchase['response']['Data'][0]['BranchID'],
                        'value_detail'=>json_encode($value_detail),  
                        'message'=>$purchase['response']['Message'],
                    ];
                    $input = PurchaseSpk::create($data_purchase);
                }else{
                    $data_purchase = [
                        'id_project'=>$request->id_project,
                        'status_purchase_spk'=>0,
                        'message'=>$purchase['response']['Message'],
                        'value_detail'=>json_encode($purchase['response']['Data']),  
                    ];
                    $input = PurchaseSpk::create($data_purchase);   
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
                $project->progres = 'Fit Out';
                $project->save();
                $store = ProjectContract::create([
                    "id_project"   =>  $request->id_project,
                    "first_party"   =>  $request->first_party,
                    "second_party"   =>  $request->second_party,
                    "nama_kontraktor"   =>  $request->nama_kontraktor,
                    "cp_kontraktor"   =>  $request->cp_kontraktor,
                    "renovation_cost"   =>  $request->renovation_cost,
                    "attachment"   =>  $attachment,
                    "nama_pic"   =>   $request->nama_pic,
                    "kontak_pic"   =>   $request->kontak_pic,
                    "lokasi_pic"   =>   $request->lokasi_pic,
                    "status"=>'Success',
                    "note"   =>  $note
                ]);
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
                return response()->json(MyHelper::checkCreate($store));
            }
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);    
    }
  
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
            $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Contract"))
            ->first();
            if($project){
                $project->progres = 'Fit Out';
                $project->save();
                $contract = ProjectContract::where(array('id_project'=>$request->id_project,'status'=>'Process'))->update([
                    'status'=>'Success'
                ]);
                $data_send = [
                    "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                    "location" => Location::where('id_partner',$project->id_partner)->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),
                ];
                $invoice = Icount::ApiInvoiceSPK($data_send,'PT IMA');
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
                    ];
                    $input = InvoiceSpk::create($data_invoice);
                    $purchase = Icount::ApiPurchaseSPK($data_send,'PT IMA');
                    if($purchase['response']['Status']=='1' && $purchase['response']['Message']=='success'){
                        $value_detail = array_map(function($value){
                            unset($value['Item']['ItemImage']);
                            return $value;
                        }, $purchase['response']['Data'][0]['Detail'] ?? []);
                        $data_purchase = [
                            'id_project'=>$request->id_project,
                            'id_request_purchase'=>$purchase['response']['Data'][0]['PurchaseRequestID'],
                            'id_business_partner'=>$purchase['response']['Data'][0]['BusinessPartnerID'],
                            'id_branch'=>$purchase['response']['Data'][0]['BranchID'],
                            'value_detail'=>json_encode($value_detail),  
                        ];
                        $input = PurchaseSpk::create($data_purchase);
                    }
                }
                return response()->json(['status' => 'success']);
            }
            return response()->json(['status' => 'fail', 'messages' => ['Proses tidak berada dalam status Contract']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function destroy(Request $request)
    {
        if($request->id_project){
        $survey = ProjectContract::where('id_project', $request->id_project)->where(array('status'=>'Process'))->delete();
        return MyHelper::checkDelete($survey);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function detail(Request $request)
    {
        if(isset($request->id_project)){
         $survey = ProjectContract::where('id_project', $request->id_project)->first();
         if($survey){
            return response()->json(['status' => 'success','result'=>$survey]);
            }
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        
    }
    
    public function no_spk() {
        $no = ProjectContract::latest()->first();
        $nomer = $no->id_projects_contract??1;
        $nomer++;
        $x = 1;
        $s = 2;
         while($x <= $s) {
            $no_spk = 'SPK/'.$nomer.'/'.date('m').'/'.date('Y');
             $no = ProjectContract::where('nomor_spk',$no_spk)->first();
            if(!$no){
                  break;
            }
            $nomer++;
            $x++;
             $s++;
          } 
        return response()->json(['status' => 'success', 'result' => $no_spk]);
    }
    public function no_loi() {
        $no = ProjectContract::latest()->first();
        $nomer = $no->id_projects_contract??1;
        $nomer++;
        $x = 1;
        $s = 2;
         while($x <= $s) {
            $no_spk = 'LOI/'.$nomer.'/'.date('m').'/'.date('Y');
            $no = ProjectContract::where('nomor_loi',$no_spk)->first();
            if(!$no){
                  break;
            }
            $nomer++;
            $x++;
            $s++;
          } 
        return response()->json(['status' => 'success', 'result' => $no_spk]);
    }
    public function invoice_spk(Request $request)
    {
        if(isset($request->id_project)){
            $project = Project::where('id_project', $request->id_project)->first();
            if($project){
                $data_send = [
                    "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                    "location" => Location::where('id_location',$project->id_location)->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),
                    "location_bundling" => LocationOutletStarterBundlingProduct::where('id_location',$project->id_location)->join('product_icounts','product_icounts.id_product_icount','location_outlet_starter_bundling_products.id_product_icount')->get(),
                ];
                $invoice = Icount::ApiInvoiceSPK($data_send,'PT IMA');
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
                        'status_invoice_spk'=>1,
                        'message'=>$invoice['response']['Message'],
                    ];
                    $input = InvoiceSpk::where(array('id_project'=>$request->id_project))->update($data_invoice);
                }else{
                    $data_invoice = [
                        'id_project'=>$request->id_project,
                        'status_invoice_spk'=>0,
                        'message'=>$invoice['response']['Message'],
                        'value_detail'=>json_encode($invoice['response']['Data']),  
                    ];
                    $input = InvoiceSpk::where(array('id_project'=>$request->id_project))->update($data_invoice);
                    return response()->json(['status' => 'fail','messages'=>$invoice['response']['Message']]);
                }
                return response()->json(['status' => 'success']);
            }
            return response()->json(['status' => 'fail', 'messages' => 'Incompleted Data']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
            
    }
    public function purchase_spk(Request $request)
    {
        if(isset($request->id_project)){
            $project = Project::where('id_project', $request->id_project)->first();
            if($project){
                $data_send = [
                    "partner" => Partner::where('id_partner',$project->id_partner)->first(),
                    "location" => Location::where('id_location',$project->id_location)->first(),
                    "confir" => ConfirmationLetter::where('id_partner',$project->id_partner)->first(),
                    "location_bundling" => LocationOutletStarterBundlingProduct::where('id_location',$project->id_location)->join('product_icounts','product_icounts.id_product_icount','location_outlet_starter_bundling_products.id_product_icount')->get(),
                ];
                $purchase = Icount::ApiPurchaseSPK($data_send,'PT IMA');
                if($purchase['response']['Status']=='1' && $purchase['response']['Message']=='success'){
                    $value_detail = array_map(function($value){
                        unset($value['Item']['ItemImage']);
                        return $value;
                    }, $purchase['response']['Data'][0]['Detail'] ?? []);
                    $data_invoice = [
                       'id_request_purchase'=>$purchase['response']['Data'][0]['PurchaseRequestID'],
                       'id_business_partner'=>$purchase['response']['Data'][0]['BusinessPartnerID'],
                       'id_branch'=>$purchase['response']['Data'][0]['BranchID'],
                       'value_detail'=>json_encode($value_detail),  
                       'message'=>$purchase['response']['Message'],
                       'status_purchase_spk'=>1,
                    ];
                    $input = PurchaseSpk::where(array('id_project'=>$request->id_project))->update($data_invoice);
                }else{
                    $data_invoice = [
                        'status_purchase_spk'=>0,
                        'message'=>$purchase['response']['Message'],
                        'value_detail'=>json_encode($purchase['response']['Data']),  
                    ];
                    $input = PurchaseSpk::where(array('id_project'=>$request->id_project))->update($data_invoice);
                    return response()->json(['status' => 'fail','messages'=>$purchase['response']['Message']]);
                }
             
                return response()->json(['status' => 'success']);
            }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
