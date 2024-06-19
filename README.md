# Microservicio De Pagos

## Descripción

El Microservicio de Pagos es un proyecto diseñado para facilitar la integración y gestión de pagos en línea utilizando la API de Epayco, una reconocida pasarela de pagos. Este microservicio proporciona una interfaz simplificada y segura para manejar transacciones, ofreciendo funcionalidades esenciales como la creación de sesiones de pago, el procesamiento de pagos con tarjeta de crédito y la gestión de suscripciones. Al centralizar y abstraer las interacciones con la API de Epayco, el microservicio permite a los desarrolladores integrar rápidamente capacidades de pago en sus aplicaciones sin preocuparse por los detalles específicos de la API subyacente.

Este proyecto está construido sobre el framework Laravel, lo que garantiza un desarrollo ágil y una estructura de código mantenible. Además, se emplean herramientas como Postman para pruebas de API y MySQL para la persistencia de datos, asegurando un entorno robusto y escalable.

## Tecnologías Usadas

- PHP
- Laravel
- Postman
- API de Epayco

## Requisitos Previos

- Composer
- Zip
- XAMPP


Asegúrate de tener instaladas estas herramientas antes de proceder con la instalación y configuración del proyecto.

## Instalación

1. **Clona el repositorio:**
    ```bash
    git clone
2. **Navega al directorio del proyecto:**
   ```bash
   cd ms-pago
   ```
3. **Instala las dependencias:**
   ```bash
   composer install
   ```
4. **Configura el archivo `.env`**:
   Copia el archivo `.env.example` y renómbralo a `.env`. Luego, configura tus credenciales de base de datos y las configuraciones de la API de Epayco.
   ```bash
   cp .env.example .env
   nano .env
   ```

5. **Genera la clave de la aplicación:**
   ```bash
   php artisan key:generate
   ```

## Uso

El proyecto proporciona una API para gestionar pagos en línea utilizando la API de Epayco. Aquí hay una descripción de cómo usar algunas de las rutas y métodos proporcionados:

1. **Crear una sesión de pago**: Esta ruta se utiliza para crear una nueva sesión de pago. Se requiere una serie de parámetros en la solicitud, incluyendo el nombre, la factura, la descripción, la moneda, la cantidad, el país, si es una prueba y la IP del cliente. La ruta es `POST /payments/createPaymentSession`.

2. **Iniciar sesión con parámetros**: Esta ruta se utiliza para autenticar al usuario. Se requieren el nombre de usuario y la contraseña en la solicitud. La ruta es `POST /payments/login`.

3. **Iniciar sesión directamente**: Esta ruta se utiliza para autenticar al usuario directamente. No se requieren parámetros en la solicitud. La ruta es `POST /payments/loginDirect`.

4. **Crear una sesión de pago directamente**: Esta ruta se utiliza para autenticar al usuario y crear una nueva sesión de pago en un solo paso. Se requieren los mismos parámetros que para la creación de una sesión de pago. La ruta es `POST /payments/createPaymentSessionDirect`.

5. **Solicitar inicio de sesión JWT**: Esta ruta se utiliza para solicitar un inicio de sesión JWT. No se requieren parámetros en la solicitud. La ruta es `POST /payments/requestJwtLogin`.

6. **Procesar el pago**: Esta ruta se utiliza para procesar un pago con tarjeta de crédito. Se requieren varios parámetros en la solicitud, incluyendo el ID de la suscripción, el valor, el tipo de documento, el número de documento, el nombre, el apellido, el correo electrónico, el teléfono celular, el teléfono, el número de tarjeta, el año de vencimiento de la tarjeta, el mes de vencimiento de la tarjeta, el CVC de la tarjeta y las cuotas. La ruta es `POST /payments/processPayment`.

7. **Pago directo con tarjeta de crédito**: Esta ruta se utiliza para realizar un pago directo con tarjeta de crédito. Se requieren los mismos parámetros que para procesar el pago. La ruta es `POST /payments/directPaymentCreditcard`.

8. **Pago directo con Daviplata**: Esta ruta se utiliza para realizar un pago directo con Daviplata. Se requieren varios parámetros en la solicitud, incluyendo el ID de la suscripción, el tipo de documento, el número de documento, el nombre, el apellido, el correo electrónico, el teléfono celular, el teléfono, el número de tarjeta, el año de vencimiento de la tarjeta, el mes de vencimiento de la tarjeta, el CVC de la tarjeta y las cuotas. La ruta es `POST /payments/directPaymentDaviplata`.

9. **Pago directo con PSE**: Esta ruta se utiliza para realizar un pago directo con PSE. Se requieren varios parámetros en la solicitud, incluyendo el ID de la suscripción, el banco, el valor, el tipo de documento, el número de documento, el nombre, el apellido, el correo electrónico, el teléfono celular, la IP, la URL de respuesta, el teléfono, el impuesto, la base imponible, la descripción, la factura, la moneda, el tipo de persona, la dirección, la URL de confirmación, el método de confirmación, el modo de prueba y varios campos extra. La ruta es `POST /payments/directPaymentPSE`.

Por favor, consulta la documentación de la API de Epayco para obtener más detalles sobre los parámetros requeridos y las respuestas esperadas.

## Estructura del Proyecto

El proyecto está estructurado en las siguientes carpetas y archivos principales:

- `app/Http/Controllers`: Contiene los controladores de Laravel.
- `app/Services`: Contiene los servicios que manejan la lógica de negocio.
- `app/Models`: Contiene los modelos de Eloquent.
- `database/migrations`: Contiene las migraciones de la base de datos.
- `routes/api.php`: Define las rutas de la API.

## ENV

```makefile
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost


DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_base_de_datos
DB_USERNAME=nombre_de_usuario_de_tu_base_de_datos
DB_PASSWORD=contraseña_de_tu_base_de_datos


EPAYCO_PUBLIC_KEY=tu_llave_publica_epayco
EPAYCO_PRIVATE_KEY=tu_llave_privada_epayco
EPAYCO_TEST=true
EPAYCO_API_URL=https://apify.epayco.co
P_CUST_ID_CLIENTE=numero_del_cliente
USER=nombre_de_usuario
PASSWORD=contraseña
```

## Autores

- Daner Alejandro Salazar Colorado
- Jaime Andrés Cardona Diaz
- Santiago García




<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
