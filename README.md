<h1>Plugin "Amortizaciones"</h1>
Plugin para FacturaScripts que permite crear amortizaciones del inmovilizado a través de las facturas de compra.
https://www.facturascripts.com
<br/>

<strong>Características:</strong>
<ul>
   <li>Crear amortizaciones desde las facturas de compra.</li>
   <li>Posibilidad de realizar la amortización sobre un artículo en concreto o sobre el total de la factura.</li>
   <li>Contabilización anual, trimestral o mensual.</li>
</ul>

<br/>
<strong>Tareas pendientes:</strong>
<ul>
   <li>Generar los ASIENTOS contables pertinentes al VENDER un amortizado.</li>
   <li>Configurar CRON para que realice los asientos automáticamente.</li>
   <li>Introducir más tipos de contabilización, actualmente solo se soporta "Constante", hay que añadir el método de "Suma de digitos".</li>
   <li>Agregar más pestañas en "amortizaciones", a parte de "pendientes", "anuladas", "completadas", "vida útil finalizada", o un desplagable que actue de filtro.</li>
   <li>Mejorar la paginación:</li>
   <li>-Si nos referimos a la paginación de la página "amortizaciones" no tiene mucho lio, sería coger cualquier optra paginación y adaptarla, la de los articulos por ejemplo.</li>
   <li>-Pero si nos referimos a una paginación en "nueva_amortización" o en "editar_amortización", es más complicado, porque aqui todas las líneas forman parte de un formulario,
   y es necesario que esten todas en el código html, por ahora la única manera que se me ocurre es utilizar la paginación de bootstrap.</li>
</ul>

<br/>
<strong>Errores:</strong>
<ul>
   <li>Si un asiento se elimina desde la contabidad/asientos, la amortización no lo tendra en cuenta, y este aparecera como contabilizado en "editar_amortizacion".</li>
   <li>Idea: Comprobar si todos los asientos de las líneas contabilizadas existen al entrar en EDITAR AMORTIZACION, sino existen, se descontabiliza la línea.</li>
   <li>Idea: Algo parecido habría que utilizar al listar pendientes.</li>
</ul>

<br/>
<strong>V4</strong>
<ul>
   <li>Posibilidad de elegir entre contabilización ANUAL, TRIMESTRAL o MENSUAL.</li>
   <li>Ahora el inicio del año fiscal no es siempre el 1 de enero, sino que coge la fecha inicial del EJERCICIO que coincida con el inicio de la amortización.</li>
   <li>Genera asientos contables.</li>
   <li>Permite FINALIZAR la vida de un amortizado, creando los asientos correspondientes.</li>
   <li>Se han introducido 2 botones que permiten ver en el periodo MÄXIMO de años establecido para 
   cada tipo de amortizado, y las SUBCUENTAS contables de cada tipo de amortizado. 
   Estos tables están en 2 HTML SEPARADOS, con la intención de que sea más sencillo adaptarlo al plan contable de cualquier país.</li>
</ul>

<br/>
<strong>V5</strong>
<ul>
   <li>Menú, ahora el enlace a las amortizaciones se encuentra en "Contabilidad" en lugar de en "Compras".</li>
   <li>Autocompletado al poner las subcuentas contables, como en los artículos.</li>
   <li>Si contabilizamos una línea y el ejercicio contable no esta creado, no lo creara, sino que te dira que lo creas e importes los datos contables.</li>
   <li>Aistente para aumentar el valor de las amortizaciones a partir de una fecha concreta, por si hay que realizar alguna reparación en un amortizado.</li>
   <li>Asistente para aumentar y disminuir los años o periodos de una amortización a partir de una fecha concreta.</li>
</ul>
