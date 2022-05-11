<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeFamily;
use Modules\Employee\Entities\EmployeeEducation;
use Modules\Employee\Entities\EmployeeEducationNonFormal;
use Modules\Employee\Entities\EmployeeJobExperience;
use Modules\Employee\Entities\EmployeeQuestions;
use Modules\Employee\Http\Requests\users_create;
use Modules\Employee\Http\Requests\status_approved;
use Modules\Employee\Http\Requests\category;
use Modules\Employee\Http\Requests\create_question;
use App\Http\Models\User;
use Modules\Employee\Entities\CategoryQuestion;
use Modules\Employee\Entities\QuestionEmployee;
use Session;
class ApiQuestionEmployeeController extends Controller
{
   public function category(category $request) {
       $post = $request->all();
       $category = CategoryQuestion::create($post);
       return MyHelper::checkGet($category);
   }
   public function create(create_question $request) {
       $post = $request->all();
       if($post['type']!="Type 1"){
       $post['question'] = json_encode($post['question']);
       }
       $category = QuestionEmployee::create($post);
       return MyHelper::checkGet($category);
   }
   public function update(Request $request) {
       $post = $request->all();
       $category = QuestionEmployee::where('id_question_employee',$post['id_question_employee'])->first();
       return MyHelper::checkGet($category);
   }
   public function list() {
       $category = CategoryQuestion::with(['questions'])->get();
       foreach ($category as $value) {
           foreach ($value['questions'] as $va) {
               $va['question'] = json_decode($va['question']);
           }
       }
       return MyHelper::checkGet($category);
   }
}
