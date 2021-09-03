<?php

namespace App\Http\Middleware;

use App\Http\Models\UserFeature;
use Modules\Users\Entities\Role;
use Closure;

class FeatureControl
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next, $feature, $feature2 = null)
  {
  	$user = $request->user();
    if ($user['level'] == "Super Admin") return $next($request);

    $granted = Role::join('roles_features', 'roles_features.id_role', 'roles.id_role')
				->where([
					['roles.id_department', $user['id_department']],
					['roles.id_job_level', $user['id_job_level']],
					['id_feature', $feature]
				])
				->first();

    if (!$granted) {
        return response()->json(['error' => 'Unauthenticated action'], 403);
    } else {
        return $next($request);
    }
  }
}
