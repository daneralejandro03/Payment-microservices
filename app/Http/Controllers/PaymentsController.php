<?php

namespace App\Http\Controllers;

use App\Services\AdonisService;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Services\EpaycoService;
use App\Services\CustomSession;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

class PaymentsController extends Controller
{
    protected $epaycoService;
    protected $customSession;
    protected $adonisService;
    protected $test;

    public function __construct(EpaycoService $epaycoService, CustomSession $customSession, AdonisService $adonisService)
    {
        $this->epaycoService = $epaycoService;
        $this->customSession = $customSession;
        $this->adonisService = $adonisService;
        $this->test = 'true';
    }

    // CREAR UNA SESSIÓN
    public function createPaymentSession(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'invoice' => 'required|string',
            'description' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'country' => 'required|string',
            'test' => $this->test,
            'ip' => 'required|string',
        ]);

        $sessionId = $this->epaycoService->createPaymentSession($validated);

        if (isset($sessionId['error'])) {
            return response()->json(['error' => $sessionId['message']], 500);
        }

        return response()->json(['sessionId' => $sessionId]);
    }

    // LOGIN CON PARAMETROS
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $token = $this->epaycoService->authenticate($username, $password);

        if (isset($token['error'])) {
            return response()->json(['error' => $token['message']], 500);
        }

        $this->epaycoService->storeBearerToken($token);

        return response()->json(['token' => $token]);
    }

    // LOGIN DIRECTO
    public function loginDirect(Request $request)
    {
        $token = $this->epaycoService->authenticateDirect();

        if (isset($token['error'])) {
            return response()->json(['error' => $token['message']], 500);
        }

        $this->epaycoService->storeBearerToken($token);
        Log::log('info', 'Bearer token stored in session: ' . $this->epaycoService->getBearerToken());

        return response()->json(['token' => $token]);
    }

    public function createPaymentSessionDirect(Request $request)
    {
        // Validar los datos de la solicitud
        $validated = $request->validate([
            'name' => 'required|string',
            'invoice' => 'required|string',
            'description' => 'required|string',
            'currency' => 'required|string',
            'amount' => 'required|string',
            'country' => 'required|string',
            'test' => $this->test,
            'ip' => 'required|string',
        ]);

        // Crear la sesión de pago y autenticar
        $sessionId = $this->epaycoService->authenticateAndCreateSession($validated);

        if (isset($sessionId['error'])) {
            return response()->json(['error' => $sessionId['message']], 500);
        }

        return response()->json(['sessionId' => $sessionId]);
    }

    // SOLICITAR LOGIN JWT
    public function requestJwtLogin()
    {
        try {
            // Realizar la solicitud para obtener el token JWT
            $token = $this->epaycoService->requestJwtLogin();

            // Verificar si ocurrió un error durante la solicitud
            if (isset($token['error'])) {
                return response()->json(['error' => $token['message']], 500);
            }

            // Almacenar el token en la sesión
            $this->epaycoService->storeBearerToken($token);

            // Devolver el token en la respuesta
            return response()->json(['token' => $token]);
        } catch (\Exception $e) {
            // Manejar cualquier excepción que pueda ocurrir durante la solicitud
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //PAGO POR TARJETA DE CREDITO
    public function processPayment(Request $request)
    {
        Log::info('Request data: ', $request->all());

        try {
            // Validar los datos de la solicitud
            $validated = $request->validate([
                'subscription_id' => 'required|string',
                'value' => 'required|string',
                'docType' => 'required|string|max:4',
                'docNumber' => 'required|string|max:20',
                'name' => 'required|string|max:50',
                'lastName' => 'required|string|max:50',
                'email' => 'required|string|email|max:50',
                'cellPhone' => 'required|string|max:10',
                'phone' => 'required|string|max:10',
                'cardNumber' => 'nullable|string|max:16',
                'cardExpYear' => 'nullable|string|max:4',
                'cardExpMonth' => 'nullable|string|max:2',
                'cardCvc' => 'nullable|string|max:4',
                'dues' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ', $e->errors());
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        }

        Log::info('Validated request data: ', $validated);

        // Verificar si el token de sesión está disponible
        $bearerToken = $this->epaycoService->getBearerToken();
        if (!$bearerToken) {
            return response()->json(['error' => 'Bearer token is not available. Please log in.'], 401);
        }
        Log::info('Bearer token received after process function: ' . $bearerToken);

        // Verificar la existencia del ID de suscripción en Adonis
        // Verificar la existencia del ID de suscripción en Adonis
        if (!$this->adonisService->checkSubscriptionExistence($validated['subscription_id'])) {
            $this->customSession->deleteToken($bearerToken); // Eliminar el token de sesión antes de retornar
            return response()->json(['error' => 'El ID de suscripción no existe'], 404);
        }

        // Procesar la transacción
        $response = $this->epaycoService->processCreditCardTransaction($validated);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['message']], 500);
        }

        // Guardar la respuesta de la transacción en la base de datos
        Payment::create([
            'subscription_id' => $validated['subscription_id'],
            'amount' => $validated['value'],
            'paymentDate' => now(), // Fecha y hora actuales
        ]);

        $this->customSession->deleteToken('epayco_bearer_token');
        return response()->json($response);
    }


    //PAGO POR TARJETA DE CREDITO DIRECTO
    public function directPaymentCreditcard(Request $request)
    {
        // Validar los datos de la solicitud
        $validated = $request->validate([
            'subscription_id' => 'required|string',
            'value' => 'required|string',
            'docType' => 'required|string|max:4',
            'docNumber' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'email' => 'required|string|email|max:50',
            'cellPhone' => 'required|string|max:10',
            'phone' => 'required|string|max:10',
            'cardNumber' => 'required|string|max:16',
            'cardExpYear' => 'required|string|max:4',
            'cardExpMonth' => 'required|string|max:2',
            'cardCvc' => 'required|string|max:4',
            'dues' => 'required|string',
        ]);

        // Realizar el login y crear la sesión de pago y se obtiene el token de Sessión
        $sessionIdResponse = $this->epaycoService->authenticateAndCreateSession([
            'name' => "New Checkout " . $validated['name'],
            'invoice' => $validated['subscription_id'],
            'description' => 'Payment for subscription ' . $validated['subscription_id'],
            'currency' => 'USD', // O cualquier otra moneda que corresponda
            'amount' => $validated['value'],
            'country' => 'CO', // Código ISO del país, por ejemplo, Colombia
            'test' => $this->test, // Si es una prueba o no
            'ip' => "186.97.212.162" //$request->ip() //, // IP del cliente
        ]);

        //NOTA SI LE COLOCO LA FUNCIÓN $request->ip() ME DA ERROR, POR ESO LE COLOCO UNA IP FIJA
        Log::info("OBTENIENDO LA IP DE MI COMPUTADORA: " . $request->ip());
        if (isset($sessionIdResponse['error'])) {
            return response()->json(['error' => $sessionIdResponse['message']], 500);
        }

        // Verificar la existencia del ID de suscripción en Adonis
        if (!$this->adonisService->checkSubscriptionExistence($validated['subscription_id'])) {
            $this->customSession->deleteToken($sessionIdResponse); // Eliminar el token de sesión antes de retornar
            return response()->json(['error' => 'El ID de suscripción no existe'], 404);
        }

        // Proceder con el proceso de pago, pasando el ID de sesión
        $response = $this->epaycoService->processCreditCardTransactionSession($validated, $sessionIdResponse);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['message']], 500);
        }

        // Verificar si la transacción fue exitosa antes de guardar en la base de datos
        if (isset($response['success']) && $response['success'] === true) {
            // Guardar la respuesta de la transacción en la base de datos
            Payment::create([
                'subscription_id' => $validated['subscription_id'],
                'amount' => $validated['value'],
                'paymentDate' => now(), // Fecha y hora actuales
            ]);
        } else {
            Log::error("Transacción fallida: " . json_encode($response));
        }

        $this->customSession->deleteToken($sessionIdResponse);

        return response()->json($response);
    }

    //PAGO POR DAVIPLATA
    public function directPaymentDaviplata(Request $request)
    {

        Log::info("ESTOY DENTRO DE LAS VALIDACIONES AQUI SIEMPRE LA CAGO");
        // Validar los datos de la solicitud
        $validated = $request->validate([
            'subscription_id' => 'required|string',
            'docType' => 'required|string|max:4',
            'document' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'email' => 'required|string|email|max:50',
            'indCountry' => 'required|string|max:3',
            'phone' => 'required|string|max:10',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'ip' => 'string|max:16',
            'currency' => 'string|max:3',
            'description' => 'string|max:255',
            'value' => 'required|string|max:255',
            'tax' => 'string|max:11',
            'taxBase' => 'string|max:11',
            'testMode' => $this->test,
            'urlResponse' => 'string|max:255',
            'urlConfirmation' => 'string|max:255',
            'methodConfirmation' => 'required|string|max:255',
        ]);

        Log::info("PASE LA VALIDACIÓN" . $validated['subscription_id']);

        // Realizar el login y crear la sesión de pago y se obtiene el token de Sessión
        $sessionIdResponse = $this->epaycoService->authenticateAndCreateSession([
            'name' => "New Checkout " . $validated['name'],
            'invoice' => $validated['subscription_id'], // Cambiar si es necesario
            'description' => 'Payment for subscription ' . $validated['subscription_id'], // Cambiar si es necesario
            'currency' => 'COP', // Cambiar si es necesario
            'amount' => $validated['value'],
            'country' => $validated['indCountry'], // Cambiar si es necesario
            'test' => $this->test, // Cambiar si es necesario
            'ip' => "186.97.212.162" //$request->ip() //, // IP del cliente
        ]);

        //NOTA SI LE COLOCO LA FUNCIÓN $request->ip() ME DA ERROR, POR ESO LE COLOCO UNA IP FIJA
        Log::info("OBTENIENDO LA IP DE MI COMPUTADORA: " . $request->ip());
        if (isset($sessionIdResponse['error'])) {
            return response()->json(['error' => $sessionIdResponse['message']], 500);
        }

        // Verificar la existencia del ID de suscripción en Adonis
        if (!$this->adonisService->checkSubscriptionExistence($validated['subscription_id'])) {
            $this->customSession->deleteToken($sessionIdResponse); // Eliminar el token de sesión antes de retornar
            return response()->json(['error' => 'El ID de suscripción no existe'], 404);
        }

        // Proceder con el proceso de pago, pasando el ID de sesión
        $response = $this->epaycoService->createDaviplataPaymentSession($validated, $sessionIdResponse);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['message']], 500);
        }

        // Verificar si la transacción fue exitosa antes de guardar en la base de datos
        if (isset($response['success']) && $response['success'] === true) {
            // Guardar la respuesta de la transacción en la base de datos
            Payment::create([
                'subscription_id' => $validated['subscription_id'],
                'amount' => $validated['value'],
                'paymentDate' => now(), // Fecha y hora actuales
            ]);
        } else {
            Log::error("Transacción fallida: " . json_encode($response));
        }

        $this->customSession->deleteToken($sessionIdResponse);

        return response()->json($response);
    }

    //REALIZAR PAGO POR PSE
    public function directPaymentPSE(Request $request)
    {
        // Validar los datos de la solicitud
        $validated = $request->validate([
            'subscription_id' => 'required|string',
            'bank' => 'required|string|max:10',
            'value' => 'required|string|max:255',
            'docType' => 'required|string|max:4',
            'docNumber' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'email' => 'required|string|email|max:50',
            'cellPhone' => 'required|string|max:10',
            'ip' => 'nullable|string',
            'urlResponse' => 'required|string|max:255',
            'phone' => 'nullable|string|max:10',
            'tax' => 'nullable|string|max:11',
            'taxBase' => 'nullable|string|max:11',
            'description' => 'nullable|string|max:255',
            'invoice' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:3',
            'typePerson' => 'nullable|string|max:1',
            'address' => 'nullable|string|max:255',
            'urlConfirmation' => 'required|string|max:255',
            'methodConfirmation' => 'nullable|string|max:255',
            'testMode' => 'nullable|string',
            'extra1' => 'nullable|string|max:255',
            'extra2' => 'nullable|string|max:255',
            'extra3' => 'nullable|string|max:255',
            'extra4' => 'nullable|string|max:255',
            'extra5' => 'nullable|string|max:255',
            'extra6' => 'nullable|string|max:255',
            'extra7' => 'nullable|string|max:255',
            'extra8' => 'nullable|string|max:255',
            'extra9' => 'nullable|string|max:255',
            'extra10' => 'nullable|string|max:255',
        ]);
        $validated['ip'] = $request->ip(); //, // IP del cliente

        Log::info("PASE LA VALIDACIÓN" . $validated['subscription_id']);

        // Realizar el login y crear la sesión de pago y se obtiene el token de Sessión
        $sessionIdResponse = $this->epaycoService->authenticateAndCreateSession([
            'name' => "New Checkout " . $validated['name'],
            'invoice' => $validated['subscription_id'], // Cambiar si es necesario
            'description' => 'Payment for subscription ' . $validated['subscription_id'], // Cambiar si es necesario
            'currency' => 'COP', // Cambiar si es necesario
            'amount' => $validated['value'],
            'country' => "CO", // Cambiar si es necesario
            'test' => $this->test, // Cambiar si es necesario
            'ip' => "186.97.212.162" //$request->ip() //, // IP del cliente
        ]);

        //NOTA SI LE COLOCO LA FUNCIÓN $request->ip() ME DA ERROR, POR ESO LE COLOCO UNA IP FIJA
        Log::info("OBTENIENDO LA IP DE MI COMPUTADORA: " . $request->ip());
        if (isset($sessionIdResponse['error'])) {
            return response()->json(['error' => $sessionIdResponse['message']], 500);
        }

        // Verificar la existencia del ID de suscripción en Adonis
        if (!$this->adonisService->checkSubscriptionExistence($validated['subscription_id'])) {
            $this->customSession->deleteToken($sessionIdResponse); // Eliminar el token de sesión antes de retornar
            return response()->json(['error' => 'El ID de suscripción no existe'], 404);
        }

        // Realizar el proceso de pago PSE
        $response = $this->epaycoService->createPSEPaymentSession($validated, $sessionIdResponse);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['message']], 500);
        }

        // Verificar si la transacción fue exitosa antes de guardar en la base de datos
        if (isset($response['success']) && $response['success'] === true) {
            // Guardar la respuesta de la transacción en la base de datos
            Payment::create([
                'subscription_id' => $validated['subscription_id'],
                'amount' => $validated['value'],
                'paymentDate' => now(), // Fecha y hora actuales
            ]);
        } else {
            Log::error("Transacción fallida: " . json_encode($response));
        }

        $this->customSession->deleteToken($sessionIdResponse);

        return response()->json($response);
    }

    // LISTAR TODOS LOS PAGOS
    public function index()
    {
        $payments = Payment::all();
        return response()->json($payments, 200);
    }

    // OBTENER UN PAGO POR ID

    public function show($id)
    {
        $the_Payment = Payment::find($id);
        if (is_null($the_Payment)) {
            return response()->json(['message' => 'Payment not found'], 404);
        } else {
            return response()->json($the_Payment, 200);
        }
    }

    // ACTUALIZAR UN PAGO POR ID

    public function update(Request $request, $id)
    {
        $the_Payment = Payment::find($id);
        if (is_null($the_Payment)) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $the_Payment->update($request->all());
        return response($the_Payment, 200);
    }

    // ELIMINAR UN PAGO POR ID
    public function destroy($id)
    {
        $the_Payment = Payment::find($id);
        if (is_null($the_Payment)) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        $the_Payment->delete();
        return response()->json(null, 204);
    }
}
