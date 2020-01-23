function makeTapList(beers){
	var locationclass = {
		6 : "serving",
		7 : "low",
		8 : "next"
	};

	$('#taplist').empty();
	$.each(beers, function(key, beer){
		/*if ( beer.location.status.toLowerCase() == 'serving' ) {*/
			if (!beer.note.trim()) {
				element = "<div class=\"card col-md-4 flex-column\">";
			} else {
				element = '<div class=\"card col-md-4 flex-column\" data-toggle="tooltip" title="'+beer.note+'">';
			}
			element += '<div class=\"card-body\">';
			element += '<h5 class=\"card-title text-muted\">'+beer.brewer.name+'</h5>';
			element += '<h4 class=\"card-subtitle mb-2\">'+beer.name+'</h4>';
			element += '<p class=\"card-text\">'+beer.style.name+' &mdash; ';
			element += '<strong>'+beer.abv+'% ABV</strong></p></div>';
			$('#taplist').append(element);
		/*}*/
	});
}

var ready = function() {

	$.ajax('https://clubfare.hs.net.nz/api/beers', {
		success: function (data, textStatus, xhr) {
			data.sort(function(a, b){
				var status1 = a.location.status.toLowerCase();
				var status2 = b.location.status.toLowerCase();
			  var brewer1 = a.brewer.name.toLowerCase();
			  var brewer2 = b.brewer.name.toLowerCase();
			  var beer1 = a.name.toLowerCase();
			  var beer2 = b.name.toLowerCase();
			  if (status1 < status2) {return 1;}
			  if (status1 > status2) {return -1;}
			  if (brewer1 < brewer2) {return -1;}
			  if (brewer1 > brewer2) {return 1;}
			  if (beer1 < beer2) {return -1;}
			  if (beer1 > beer2) {return 1;}
			  return 0;
			});
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
