@extends('layouts.app')

@section('content')
<div class="maindivsteps">
	<style type="text/css">
		.steps p{
		    font-size: 16px;
		    color: #000;
		}
		code {
		    font-size: 14px !important;
		    color: #000 !important;
		    word-break: break-word !important;
	        border: 1px solid #d3d3d3;
		    float: left;
		    background: #f1f1f1;
		    padding: 10px;
		    width: 100%;
		}
		.maindivsteps{
			padding: 10px 2%;
		}
		.stepheadings{
		    background: #F1F1F1;
		    padding: 10px;
		    color:#f00;
		}
		.m20{
			margin-left: 20px;
		}
		xmp{
		    margin-top: 0px;
		}
	</style>
    <div class="col-sm-12">
		<h3 class="stepheadings">Important Instructions</h3>         
    </div>
    <div class="col-sm-12 text-left">
		<h5>Please follow following Simple steps to get started! </h5>
		<p style="color:#000;">Click on Edit Code for your current theme. You will get Layout, Templates, Sections, Snippets, assets etc options in left hand side of your content area section. </p>
		<ol class="steps">
			<li>
				<p> </p> 
				<code>
					
				</code><br> 
				<p></p>
			</li>
			<li>
				<p></p>
			</li>
			<li>
				<p></p>
			</li>
			<li>
				<p></p>
			</li>
			<li>
				<p></p>
			</li>
			<li>
				<p>That's it, Enjoy!</p>
			</li>
		</ol>
	</div>
</div>
@endsection