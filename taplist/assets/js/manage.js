
// api url
const api_url = "/beers/api/beer_api.php";

// setup post function
function postapi(url,data) {
  // Storing response
  const body = {'function': url, 'data': JSON.stringify(data) };
  const response = fetch(api_url, {
    method: 'POST',
    body: JSON.stringify(body),
    headers: {
      "Content-Type": "application/json; charset=UTF-8"
    }
  })
  .then((response) => response.json())
  .then((data) => console.log(data));
  // Storing data in form of JSON
  // var data = response.json();
  if (response) {
    console.log(response);
    // return data;
  }
}

// Defining async get function
async function getapi(url) {
    // Storing response
    const response = await fetch(api_url+'?function='+url);    
    // Storing data in form of JSON
    var data = await response.json();
    if (response) {
      return data;
    }
}

// Calling that async function, then sending the result to "show"
function manage_kegs(){
  getapi('getKegs').then( res => {
    show( res, kegsAvailable() );
  });
}

function manage_taps(){
  getapi('getOnTap').then( res => {
    show( res, kegOrdersTemplate() );
  });
}

// render the data as per template using mustache.js
function show(data, templateHtml) {
  var renderedList  = Mustache.render( templateHtml, data );
  if ( document.getElementById( "contents" ).innerHTML !== renderedList ) {
    document.getElementById( "contents" ).innerHTML = renderedList;
  }
}



function kegOrdersTemplate() {
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

function kegsAvailable() {
  // https://medium.com/@snowleo208/how-to-create-responsive-table-d1662cb62075
  return `
    <div class="table-container" role="table">
      <div class="flex-table header" role="rowgroup">
        <div class="flex-row first" role="columnheader">Beer - Brewery</div>
        <div class="flex-row" role="columnheader">Price - Received</div>
        <div class="flex-row" role="columnheader">half - handle - jug</div>
        <div class="flex-row" role="columnheader">On Tap - Empty</div>
      </div>
      <div class="flex-table scrollable">
        <div class="flex-table row" role="rowgroup">
          {{#.}}
          <div class="flex-row first" role="cell">{{beer}} - {{brewery}}</div>
          <div class="flex-row" role="cell">{{price}} - {received_date}}</div>
          <div class="flex-row" role="cell">{{280_price}} - {{440_price}} - {{980_price}}</div>
          <div class="flex-row" role="cell">{{on_tap}} - {{off_tap}}</div>
          {{/.}}
        </div>
        <div class="flex-table row" role="rowgroup">
          {{#.}}
          <div class="flex-row first" role="cell">{{beer}} - {{brewery}}</div>
          <div class="flex-row" role="cell">{{price}} - {received_date}}</div>
          <div class="flex-row" role="cell">{{280_price}} - {{440_price}} - {{980_price}}</div>
          <div class="flex-row" role="cell">{{on_tap}} - {{off_tap}}</div>
          {{/.}}
        </div>
        <div class="flex-table row" role="rowgroup">
          {{#.}}
          <div class="flex-row first" role="cell">{{beer}} - {{brewery}}</div>
          <div class="flex-row" role="cell">{{price}} - {received_date}}</div>
          <div class="flex-row" role="cell">{{280_price}} - {{440_price}} - {{980_price}}</div>
          <div class="flex-row" role="cell">{{on_tap}} - {{off_tap}}</div>
          {{/.}}
        </div>
      </div>
    </div>
  `;
}