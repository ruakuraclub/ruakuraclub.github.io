var sheetUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSeO6qzR59_2q-HSg6Z9zSyVwdFJWsFL5Duny50iR2n0ONqKeNmIQmUlFmi1SNBXXjpG_4Y0Yfztk3Z/pub?gid=0&single=true&output=csv";

function init() {
 Papa.parse( sheetUrl, { download: true, header: true, complete: displayTaps } );
}

function displayTaps( results ) {
  var beerList = { beers: [] };
  var sheet = results.data;
  $('#taplist').empty();
  for(var idx = 0; idx < 12; idx++ ) {
    var tap = sheet[idx];
    if ( tap['Class'].toLowerCase() == 'offline' ) { continue; } // Skip offline taps.
    
    if ( tap['Class'].toLowerCase() == 'nis' ) { continue; } // Skip taps not in service.

    if(tap['Name'] == '') { // Tap with no beer assigned
      tap['Name'] = '\xa0'; // Non-Breaking Space, ensures row heights match up
    }

    if( !tap['Badge'].includes('http') ){
      tap['Badge'] = '/taplist/'+tap['Badge'];
    }

    let field = 'ABV'
    let abv = parseFloat(tap[field])
    if(!isNaN(abv)) {
      tap[field] = sprintf("%0.1f", abv) + '%';
    } else if(tap[field] == '') {
      tap[field] = '-';
    }

    field = 'half-price'
    let price = parseFloat(tap[field])
    if(!isNaN(price)) {
      tap[field] = "$" + sprintf("%0.2f", price);
    } else if(tap[field] == '') {
      tap[field] = '-';
    }

    field = 'handle-price'
    price = parseFloat(tap[field])
    if(!isNaN(price)) {
      tap[field] = "$" + sprintf("%0.2f", price);
    } else if(tap[field] == '') {
      tap[field] = '-';
    }

    field = 'rigger'
    let takehome = parseFloat(tap[field])
    if(!isNaN(takehome)) {
      tap[field] = "$" + sprintf("%0.2f", takehome);
    } else if(tap[field] == '') {
      tap[field] = '-';
    }

      element = "<div class=\"card col-lg-4 col-md-6 col-sm-12 flex-column\">";
      element += '<div class=\"card-body\">';
      if(tap['Badge'] !== '') {
        element += '<img class=\"float-left\" src=\"'+tap["Badge"]+'\"></img>';
      }
      element += '<h4 class=\"card-title\">'+tap['Name']+'</h4>';
      element += '<div class=\"card-subtitle mb-2 text-muted\">'+tap['Brewery']+'</div>';
      element += '<p class=\"card-text\">'+tap['Style']+' &mdash; ';
      element += '<strong>'+tap['ABV']+' ABV</strong></p>';

      $('#taplist').append(element);
  }
}

window.addEventListener("DOMContentLoaded", init);
