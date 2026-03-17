<!--<div id="TA_linkingWidgetRedesign586" class="TA_linkingWidgetRedesign"><ul id="qZvXegJt" class="TA_links 2MWYN83"><li id="jmw37Ped" class="KYEFkpjwBK"><a target="_blank" href="https://www.tripadvisor.in/"><img src="https://www.tripadvisor.in/img/cdsi/partner/tripadvisor_logo_115x18-15079-2.gif" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=linkingWidgetRedesign&amp;uniq=586&amp;locationId=12495094&amp;lang=en_IN&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>

<div id="TA_cdsratingsonlynarrow243" class="TA_cdsratingsonlynarrow"><ul id="PEonV8I" class="TA_links iO2x33rzJJzZ"><li id="09wQyevlpY5M" class="BcOaLM3FcQuP"><a target="_blank" href="https://www.tripadvisor.in/"><img src="https://www.tripadvisor.in/img/cdsi/img2/branding/tripadvisor_logo_transp_340x80-18034-2.png" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=cdsratingsonlynarrow&amp;uniq=243&amp;locationId=12495094&amp;lang=en_IN&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>

<div id="TA_selfserveprop645" class="TA_selfserveprop"><ul id="z22jOBb" class="TA_links RjhAx7"><li id="Ztn8o1" class="J0ZnmTKKz51w"><a target="_blank" href="https://www.tripadvisor.in/"><img src="https://www.tripadvisor.in/img/cdsi/img2/branding/150_logo-11900-2.png" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=selfserveprop&amp;uniq=645&amp;locationId=12495094&amp;lang=en_IN&amp;rating=true&amp;nreviews=5&amp;writereviewlink=true&amp;popIdx=true&amp;iswide=false&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
-->

<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Google Review Embed in HTML website</title>
	 <style>
#map-plug {
	display:none;
}
#google-reviews {
	display:flex;
	flex-wrap:wrap;
	/*display: grid;
	grid-template-columns: repeat( auto-fit, minmax(320px, 1fr));
	*/
}
.review-item {
	border:solid 1px rgba(190,190,190,.35);
	margin:0 auto;
	padding:1em;
	flex: 1 1 20%;
}
@media ( max-width:1200px) {
	.review-item {
	flex: 1 1 40%;
	}
}
@media ( max-width:450px) {
	.review-item {
		flex: 1 1 90%;
	}
}
.review-meta, .review-stars {
	text-align:center;
	font-size:115%;
}
.review-author {
	text-transform: capitalize;
	font-weight:bold;
}
.review-date {
	opacity:.6;
	display:block;
}
.review-text {
	line-height:1.55;
	text-align:left;
	max-width:32em;
	margin:auto;
}
.review-stars ul {
	display: inline-block;
	list-style: none !important;
	margin:0;
	padding:0;
}
.review-stars ul li {
	float: left;
	list-style: none !important;
	margin-right: 1px;
	line-height:1;
}
.review-stars ul li i {
	color: #E4B248;
	font-size: 1.4em;
	font-style:normal;
}
.review-stars ul li i.inactive {
	color: #c6c6c6;
}
.star:after {
	content: "\2605";
}

</style>
</head>
<body>
<h1>BigCup Cafe - TripAdvisor</h1>
<div id="TA_selfserveprop645" class="TA_selfserveprop"><ul id="z22jOBb" class="TA_links RjhAx7"><li id="Ztn8o1" class="J0ZnmTKKz51w"><a target="_blank" href="https://www.tripadvisor.in/"><img src="https://www.tripadvisor.in/img/cdsi/img2/branding/150_logo-11900-2.png" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=selfserveprop&amp;uniq=645&amp;locationId=12495094&amp;lang=en_IN&amp;rating=true&amp;nreviews=5&amp;writereviewlink=true&amp;popIdx=true&amp;iswide=false&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>

<h1>Google Reviews</h1>
<div id="google-reviews"></div>
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="big-cup.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=AIzaSyDD6cbgN3hU4x6LLLGBvyOoeO0EZsAmNaM&signed_in=true&libraries=places"></script> 
<!-- it is your Google API key 'AIzaSyBzmKmgtHFnvDsDdZtIF8xIqUJX9NS9EyY' --> 
<script>
	jQuery(document).ready(function( $ ) {
	   $("#google-reviews").googlePlaces({
	        placeId: 'ChIJAUFg7HsApTsR7UJAGEZThig' //Find placeID @: https://developers.google.com/places/place-id
	      , render: ['reviews']
	      , min_rating: 4
	      , max_rows: 5
	   });
	});
</script>
 
</body>
</html>





