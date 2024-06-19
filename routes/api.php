<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentsController;


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('payments')->group(function () {

    //ESTAS SON PETICIONES DE PRUEBA, SON CORRECTAS PERO NO SE USAN
    Route::post('login', [PaymentsController::class, 'login']);
    Route::post('createPaymentSession', [PaymentsController::class, 'createPaymentSession']);
    Route::post('createPaymentSessionDirect', [PaymentsController::class, 'createPaymentSessionDirect']);
    Route::post('loginDirect', [PaymentsController::class, 'loginDirect']);
    Route::post('requestJwtLogin', [PaymentsController::class, 'requestJwtLogin']);
    Route::post('processPayment', [PaymentsController::class, 'processPayment']);

    //PETICIONES CORRECTAS

    //PERICIÓN 1: PAGO POR TARJETA DE CREDITO
    Route::post('directPaymentCreditcard', [PaymentsController::class, 'directPaymentCreditcard']);

    //PETICIÓN 2: PAGO POR DAVIPLATA
    Route::post('directPaymentDaviplata', [PaymentsController::class, 'directPaymentDaviplata']);

    //PETICIÓN 3: PAGO POR PSE
    Route::post('directPaymentPSE', [PaymentsController::class, 'directPaymentPSE']);

    //PETICIÓN 4: LISTAR TODOS LOS PAGOS
    Route::get('index', [PaymentsController::class, 'index']);

    //PETICIÓN 5: LISTAR UN PAGO POR ID
    Route::get('show/{id}', [PaymentsController::class, 'show']);

    //PETICIÓN 6: ACTUALIZAR UN PAGO POR ID
    Route::put('update/{id}', [PaymentsController::class, 'update']);

    //PETICIÓN 7: ELIMINAR UN PAGO POR ID
    Route::delete('destroy/{id}', [PaymentsController::class, 'destroy']);

});
