<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Http\Models\ManualPayment;

class InManualPayment implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ManualPayment::where('id_manual_payment', $value)->first();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Manual payment not registered.';
    }
}
