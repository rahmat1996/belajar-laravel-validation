<?php

namespace Tests\Feature;

use App\Rules\RegistrationRule;
use App\Rules\Uppercase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

use function PHPSTORM_META\map;

class ValidatorTest extends TestCase
{
    public function testValidator()
    {
        $data = [
            "username" => "Rahmat",
            "password" => "123456"
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
    }

    public function testValidatorInvalid()
    {
        $data = [
            "username" => "",
            "password" => ""
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorValidationException()
    {
        $data = [
            "username" => "",
            "password" => ""
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);

        try {
            $validator->validate();
            $this->fail("ValidationException not thrown");
        } catch (ValidationException $exception) {
            $this->assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorMultipleRules()
    {
        App::setLocale("id");

        $data = [
            "username" => "eko",
            "password" => "eko"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required", "min:6", "max:20"]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorValidData()
    {
        $data = [
            "username" => "rahmat@test.com",
            "password" => "123456",
            "admin" => true
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => "required|min:6|max:20"
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);

        try {
            $valid = $validator->validate();
            Log::info(json_encode($valid, JSON_PRETTY_PRINT));
        } catch (ValidationException $exception) {
            $this->assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorInlineMessage()
    {
        App::setLocale("id");

        $data = [
            "username" => "eko",
            "password" => "eko"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required", "min:6", "max:20"]
        ];

        $messages = [
            "required" => ":attribute harus diisi",
            "email" => ":attribute harus berupa email",
            "min" => ":attribute minimal :min karakter",
            "max" => ":attribute maksimal :max karakter",
        ];

        $validator = Validator::make($data, $rules, $messages);
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorAdditionalValidation()
    {
        App::setLocale("id");

        $data = [
            "username" => "rahmat@test.com",
            "password" => "rahmat@test.com"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required", "min:6", "max:20"]
        ];

        $validator = Validator::make($data, $rules);
        $validator->after(function (ValidationValidator $validator) {
            $data = $validator->getData();
            if ($data['username'] == $data['password']) {
                $validator->errors()->add("password", "Password tidak boleh sama dengan username");
            }
        });
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomRule()
    {
        $data = [
            "username" => "rahmat@test.com",
            "password" => "rahmat@test.com"
        ];

        $rules = [
            "username" => ["required", "email", "max:100", new Uppercase()],
            "password" => ["required", "min:6", "max:20", new RegistrationRule()]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomFunctionRule()
    {
        $data = [
            "username" => "rahmat@test.com",
            "password" => "rahmat@test.com"
        ];

        $rules = [
            "username" => ["required", "email", "max:100", function (string $attribute, string $value, \Closure $fail) {
                if ($value !== strtoupper($value)) {
                    $fail("The field $attribute must be UPPERCASE");
                }
            }],
            "password" => ["required", "min:6", "max:20", new RegistrationRule()]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorRuleClasses()
    {
        $data = [
            "username" => "Rahmat",
            "password" => "rahmat123@test.com"
        ];

        $rules = [
            "username" => ["required", new In(["Rahmat", "Budi", "Joko"])],
            "password" => ["required", Password::min(6)->letters()->numbers()->symbols()]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertNotNull($validator);
        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());

        $message = $validator->getMessageBag();
        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testNestedArray()
    {
        $data = [
            "name" => [
                "first" => "Rahmat",
                "last" => "Saja"
            ],
            "address" => [
                "street" => "Jalan Kucing",
                "city" => "Jakarta",
                "country" => "Indonesia"
            ]
        ];

        $rules = [
            "name.first" => ["required", "max:100"],
            "name.last" => ["max:100"],
            "address.street" => ["max:200"],
            "address.city" => ["required", "max:100"],
            "address.country" => ["required", "max:100"]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }

    public function testNestedIndexedArray()
    {
        $data = [
            "name" => [
                "first" => "Rahmat",
                "last" => "Saja"
            ],
            "address" => [
                [
                    "street" => "Jalan Kucing",
                    "city" => "Jakarta",
                    "country" => "Indonesia"
                ],
                [
                    "street" => "Jalan Ayam",
                    "city" => "Bandung",
                    "country" => "Indonesia"
                ]
            ]
        ];

        $rules = [
            "name.first" => ["required", "max:100"],
            "name.last" => ["max:100"],
            "address.*.street" => ["max:200"],
            "address.*.city" => ["required", "max:100"],
            "address.*.country" => ["required", "max:100"]
        ];

        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());
    }
}
