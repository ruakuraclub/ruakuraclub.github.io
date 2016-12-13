function makeTapList(beers){
	var locationclass = {
		6 : "serving",
		7 : "low",
		8 : "next"
	};

	$('#taplist ul').empty();
	$.each(beers, function(key, beer){
		element = "";
		if (!beer.note.trim()) {
			element += '<li class=\"'+locationclass[beer.location.id]+'\">';
		} else {
			element += '<li class=\"'+locationclass[beer.location.id]+'\" data-toggle="tooltip" title="'+beer.note+'">';
		}
		element += '<h2>'+beer.brewer.name+' <em>'+beer.name+'</em></h2>';
		element += beer.style.name+' &mdash;';
		element += '<strong>'+beer.abv+'% ABV</strong><br>';
		element += beer.brewer.location+'<br>';
		element += '<p></p></li>';
		$('#taplist ul').append(element);
	});

	$('[data-toggle="tooltip"]').tooltip({
		'placement': 'right'
	});
}

var ready = function() {

	$.ajax('https://clubfare.hs.net.nz/api/beers', {
		success: function (data, textStatus, xhr) {
			makeTapList(data);
		}
	});

	var etag = null;
	setInterval(function () {
		$.ajax('https://clubfare.hs.net.nz/api/beers', {
			success: function (data, textStatus, xhr) {
				if (etag == null) {
					etag = xhr.getResponseHeader('Etag');
				}

				if (xhr.getResponseHeader('ETag') != etag) {
					// Page needs refreshing because etag has changed
					makeTapList(data);
				}
			}
		});
	}, 10000);
};

$(document).ready(ready);
$(document).on('page:load', ready);
