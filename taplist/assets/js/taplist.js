
// api url
const api_url = "/beers/api/beer_api.php";

// Defining async function
async function getapi(url) {
    // Storing response
    const response = await fetch(api_url+'?function='+url);
    
    // Storing data in form of JSON
    var data = await response.json();
    if (response) {
      return data;
    }
}

// Calling that async function automatically when this file loads.
getapi('getOnTap').then( res => {
  show( res, getTapTemplate() );
});

function show(data, templateHtml) {
  var renderedList  = Mustache.render( templateHtml, data );
  if ( document.getElementById( "taps" ).innerHTML !== renderedList ) {
    document.getElementById( "taps" ).innerHTML = renderedList;
  }
}

function getTapTemplate() {
  return `
    <div class="labelHeader">
      <div class="labels withJug">
        <div class=""></div>
        <div class="abv">ABV</div>
        <div class="half">Half</div>
        <div class="handle">Handle</div>
        <div class="jug">Jug</div>
      </div>
      <div class="labels withJug">
        <div class=""></div>
        <div class="abv">ABV</div>
        <div class="half">Half</div>
        <div class="handle">Handle</div>
        <div class="jug">Jug</div>
      </div>
    </div>
    <div class="tapContentsWithJug">
      {{#.}}
      <div class="beersWithJug {{Class}}">
        <div class="badge">
          <img src="{{Badge}}">
        </div>
        <div class="nameWithJug">{{beer}}<span>{{brewery}}</span></div>
        <div class="beerStyle">{{style}}</div>
        <div class="values abv">{{ABV}}</div>
        <div class="values half">{{half-price}}</div>
        <div class="values handle">{{handle-price}}</div>
        <div class="values jug">{{jug-price}}</div>
      </div>
      {{/.}}
    </div>`;
}
