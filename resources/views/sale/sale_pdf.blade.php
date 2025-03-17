<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <title>Descargar Contrato</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  {{-- <link rel="stylesheet" href="{{ asset('pdf/modern-normalize.css') }}"> --}}
  {{-- <link rel="stylesheet" href="{{ asset('pdf/web-base.css') }}"> --}}
  {{-- <link rel="stylesheet" href="{{ asset('pdf/invoice.css') }}"> --}}
    <style>
        *,
*::before,
*::after {
	box-sizing: border-box;
}

/**
Use a more readable tab size (opinionated).
*/

:root {
	-moz-tab-size: 4;
	tab-size: 4;
}

/**
1. Correct the line height in all browsers.
2. Prevent adjustments of font size after orientation changes in iOS.
*/

html {
	line-height: 1.15; /* 1 */
	-webkit-text-size-adjust: 100%; /* 2 */
}

/*
Sections
========
*/

/**
Remove the margin in all browsers.
*/

body {
	margin: 0;
}

/**
Improve consistency of default fonts in all browsers. (https://github.com/sindresorhus/modern-normalize/issues/3)
*/

body {
	font-family:
		system-ui,
		-apple-system, /* Firefox supports this but not yet `system-ui` */
		'Segoe UI',
		Roboto,
		Helvetica,
		Arial,
		sans-serif,
		'Apple Color Emoji',
		'Segoe UI Emoji';
}

/*
Grouping content
================
*/

/**
1. Add the correct height in Firefox.
2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
*/

hr {
	height: 0; /* 1 */
	color: inherit; /* 2 */
}

/*
Text-level semantics
====================
*/

/**
Add the correct text decoration in Chrome, Edge, and Safari.
*/

abbr[title] {
	text-decoration: underline dotted;
}

/**
Add the correct font weight in Edge and Safari.
*/

b,
strong {
	font-weight: bolder;
}

/**
1. Improve consistency of default fonts in all browsers. (https://github.com/sindresorhus/modern-normalize/issues/3)
2. Correct the odd 'em' font sizing in all browsers.
*/

code,
kbd,
samp,
pre {
	font-family:
		ui-monospace,
		SFMono-Regular,
		Consolas,
		'Liberation Mono',
		Menlo,
		monospace; /* 1 */
	font-size: 1em; /* 2 */
}

/**
Add the correct font size in all browsers.
*/

small {
	font-size: 80%;
}

/**
Prevent 'sub' and 'sup' elements from affecting the line height in all browsers.
*/

sub,
sup {
	font-size: 75%;
	line-height: 0;
	position: relative;
	vertical-align: baseline;
}

sub {
	bottom: -0.25em;
}

sup {
	top: -0.5em;
}

/*
Tabular data
============
*/

/**
1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
*/

table {
	text-indent: 0; /* 1 */
	border-color: inherit; /* 2 */
}

/*
Forms
=====
*/

/**
1. Change the font styles in all browsers.
2. Remove the margin in Firefox and Safari.
*/

button,
input,
optgroup,
select,
textarea {
	font-family: inherit; /* 1 */
	font-size: 100%; /* 1 */
	line-height: 1.15; /* 1 */
	margin: 0; /* 2 */
}

/**
Remove the inheritance of text transform in Edge and Firefox.
1. Remove the inheritance of text transform in Firefox.
*/

button,
select { /* 1 */
	text-transform: none;
}

/**
Correct the inability to style clickable types in iOS and Safari.
*/

button,
[type='button'],
[type='reset'],
[type='submit'] {
	-webkit-appearance: button;
}

/**
Remove the inner border and padding in Firefox.
*/

::-moz-focus-inner {
	border-style: none;
	padding: 0;
}

/**
Restore the focus styles unset by the previous rule.
*/

:-moz-focusring {
	outline: 1px dotted ButtonText;
}

/**
Remove the additional ':invalid' styles in Firefox.
See: https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737
*/

:-moz-ui-invalid {
	box-shadow: none;
}

/**
Remove the padding so developers are not caught out when they zero out 'fieldset' elements in all browsers.
*/

legend {
	padding: 0;
}

/**
Add the correct vertical alignment in Chrome and Firefox.
*/

progress {
	vertical-align: baseline;
}

/**
Correct the cursor style of increment and decrement buttons in Safari.
*/

::-webkit-inner-spin-button,
::-webkit-outer-spin-button {
	height: auto;
}

/**
1. Correct the odd appearance in Chrome and Safari.
2. Correct the outline style in Safari.
*/

[type='search'] {
	-webkit-appearance: textfield; /* 1 */
	outline-offset: -2px; /* 2 */
}

/**
Remove the inner padding in Chrome and Safari on macOS.
*/

::-webkit-search-decoration {
	-webkit-appearance: none;
}

/**
1. Correct the inability to style clickable types in iOS and Safari.
2. Change font properties to 'inherit' in Safari.
*/

::-webkit-file-upload-button {
	-webkit-appearance: button; /* 1 */
	font: inherit; /* 2 */
}

/*
Interactive
===========
*/

/*
Add the correct display in Chrome and Safari.
*/

summary {
	display: list-item;
}
    </style>
  <style>
      body {
  font-size: 13px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

table tr td {
  padding: 0;
}

table tr td:last-child {
  text-align: right;
}

.bold {
  font-weight: bold;
}

.right {
  text-align: right;
}

.large {
  font-size: 1.2em;
}

.total {
  font-weight: bold;
  color: #fb7578;
}

.logo-container {
  margin: 20px 0 30px 0;
}

.invoice-info-container {
  font-size: 0.875em;
}
.invoice-info-container td {
  padding: 4px 0;
}

.client-name {
  font-size: 1.5em;
  vertical-align: top;
}

.line-items-container {
  margin: 15px 0;
  font-size: 0.875em;
}

.line-items-container th {
  text-align: left;
  color: #999;
  border-bottom: 2px solid #ddd;
  padding: 10px 0 15px 0;
  font-size: 0.75em;
  text-transform: uppercase;
}

.line-items-container th:last-child {
  text-align: right;
}

.line-items-container td {
  padding: 5px 0;
}

/* .line-items-container tbody tr:first-child td {
  padding-top: 10px;
} */

.line-items-container.has-bottom-border tbody tr:last-child td {
  padding-bottom: 25px;
  border-bottom: 2px solid #ddd;
}

.line-items-container.has-bottom-border {
  margin-bottom: 0;
}

.line-items-container th.heading-quantity {
  width: 50px;
}
.line-items-container th.heading-price {
  text-align: right;
  width: 100px;
}
.line-items-container th.heading-subtotal {
  width: 100px;
}

.payment-info {
  width: 38%;
  font-size: 0.75em;
  line-height: 1.5;
}

.footer {
  margin-top: 30px;
}

.footer-thanks {
  font-size: 1.125em;
}

.footer-thanks img {
  display: inline-block;
  position: relative;
  top: 1px;
  width: 16px;
  margin-right: 4px;
}

.footer-info {
  float: right;
  margin-top: 5px;
  font-size: 0.75em;
  color: #ccc;
}

.footer-info span {
  padding: 0 5px;
  color: black;
}

.footer-info span:last-child {
  padding-right: 0;
}

.page-container {
  display: none;
}
.page-break {
    page-break-after: always;
}

.number-clausulas{
    /* display: -webkit-inline-box;
    display: -webkit-box; */
}
.number-clausulas p{
    margin: 0;
    text-align: justify;
    font-size: 0.76rem; /*0.68rem*/;
}
.number-clausulas strong{
    float: left;
    font-size: 0.76rem; /*0.68rem*/;
}
.number-clausulas ul li{
    font-size: 0.76rem; /*0.68rem*/;
}
.place-date{
    text-align: right;
}
.place-date p{
    /* font-size: small; */
    font-size: 0.6rem;
}
  </style>
  {{-- <script type="text/javascript" src="./web/scripts.js"></script> --}}
</head>
<body>

{{-- <button type="button" class="btn btn-light-primary font-weight-bold" onclick="window.print();">Imprimir Pedido</button> --}}
<div class="web-container">

  <!-- See invoice.html! It is injected here... -->
  <div class="page-container">
    Page
    <span class="page"></span>
    of
    <span class="pages"></span>
  </div>

  <div class="logo-container">
    <table>
        <tbody>
            <tr>
                <td style="padding: 0 !important;border-bottom:none;">
                    <img
                        style="width:210px;height: 65px;background:black;border-radius:5px;"
                        src="{{ public_path('laravest.png') }}"
                    >
                </td>
                <td style="padding: 0 !important;border-bottom:none;">
                    <br>
                    <small>Tel. 099 122 6607</small>
                    <br>
                    <small>Km. 6,5 via Daule Lotizacion Santa <br> Adriana Mz. 25 Solar 11 090505 <br> Guayaquil, Ecuador </small>
                </td>
            </tr>
        </tbody>
    </table>

  </div>
  <div style="clear:both;"></div>
  <table class="invoice-info-container">
    <tr>
        <td>
          N° VENTA: <strong>#{{ $sale->n_transaccion }}</strong>
        </td>

        <td>
          FECHA: {{ $sale->created_at->format("Y-m-d h:i A") }}
        </td>
    </tr>
    <div class="" style="display: block;width:100%;border:1px solid black;height:1px;"></div>
    <tr>
        {{-- rowspan="2" --}}
      <td class="">
        <b>Datos del cliente: </b>
        <br>
        <br>
        CLIENTE: {{ $sale->user->name. ' ' .$sale->user->surname }}
        <br>
        CORREO: {{ $sale->user->email }}
      </td>
      <td>
        DOC: 00000000
      </td>
    </tr>
    <tr>
        <td></td>
        <td>TELÉFONO: {{ $sale->user->phone }}</td>
    </tr>
    <div class="" style="display: block;width:100%;border:1px solid black;height:1px;"></div>
    <tr>
        <td>
            <b>Datos de Envío: </b>
            <br>
            DIRECCIÓN: <strong>{{ $sale->sale_addres->address }}</strong>
            <br>
            EMPRESA: <strong>{{ $sale->sale_addres->company }}</strong>
            <br>
            REGION/CIUDAD: <strong>{{ $sale->sale_addres->country_region}} / {{ $sale->sale_addres->city }}</strong>
        </td>
        <td></td>
    </tr>


  </table>
  <table class="line-items-container">
    <thead>
      <tr>
        <th class="heading-quantity">Qty</th>
        <th class="heading-description">Descripción</th>
        <th class="heading-price">V. Unitario</th>
        <th class="heading-subtotal">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($sale->sale_details as $item)
        <tr>
            <td>{{ $item->quantity }}</td>
            <td>
                {{ $item->product->title }}
                @if ($item->product_variation)
                <br />
                    <strong>{{ $item->product_variation->attribute->name }}:</strong>
                    {{ $item->product_variation->propertie ? $item->product_variation->propertie->name : $item->product_variation->value_add }}
                    @if ($item->product_variation->variation_father)
                        <br />
                        <strong>{{ $item->product_variation->variation_father->attribute->name }}:</strong>
                        {{ $item->product_variation->variation_father->propertie ? $item->product_variation->variation_father->propertie->name : $item->product_variation->variation_father->value_add }}
                        
                    @endif
                @endif
                @if ($item->discount > 0)
                <br />
                  <strong> Descuento:  - {{ $item->discount  }} {{ $item->type_discount == 1 ? '%' : $item->currency }}</strong>
                  @if ($item->type_campaing)
                    Campaña: {{ $item->code_discount }}
                  @else
                     Cupon: {{ $item->code_cupon }}
                  @endif
                @endif
            </td>
            <td class="right">{{ $item->subtotal }} {{ $item->currency }}</td>
            <td class="bold">{{ $item->total }} {{ $item->currency }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>


  <table class="line-items-container has-bottom-border">
    <thead>
      <tr>
        <th>Metodo de Pago</th>
        <th>Fecha</th>
        <th>Información de Pago</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="payment-info">
            <div>
                METODO DE PAGO: <strong>{{ $sale->method_payment }}</strong>
            </div>
        </td>

        <td class="large">{{ $sale->created_at->format("Y-m-d h:i A") }}</td>

        <td class="payment-info">
            <div class="large total">
                TOTAL: {{ $sale->total }} {{ $sale->currency_payment }}
            </div>
        </td>
    </tbody>
  </table>

    <div class="footer">
        <div class="footer-info">
            <span> ANOTACIONES FINALES: {{ $sale->description }} </span>
        </div>
    </div>

</div>


</body></html>
