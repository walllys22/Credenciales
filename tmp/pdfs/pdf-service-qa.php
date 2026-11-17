<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$request=Illuminate\Http\Request::create('http://127.0.0.1:8000/inscripciones/imprimir?evento_id=1');
$request->setLaravelSession(app('session')->driver());
app()->instance('request',$request);
auth()->setUser(App\Models\User::where('role','superadmin')->firstOrFail());
view()->share('errors',new Illuminate\Support\ViewErrorBag());
$data=app(App\Http\Controllers\InscripcionController::class)->imprimir($request)->getData();
$original=$data['inscripciones'];$expanded=collect();
for($i=0;$i<60;$i++) $expanded->push(clone $original[$i % $original->count()]);
$data['inscripciones']=$expanded;
file_put_contents(__DIR__.'/reporte-qa.pdf',app(App\Support\ReportePdf::class)->generar($data));
echo 'Generated PDF for 60 rows using production service'.PHP_EOL;
