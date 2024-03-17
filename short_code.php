<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('kaarten', plugin_dir_url(__FILE__)  . 'kaarten.css');
    // wp_enqueue_script('foto_album', plugin_dir_url(__FILE__)  . 'slideshow.js', ['jquery']);
});


function kaarten_shortcode($atts) {

    $options['domain'] = isset($options['domain']) ? $options['domain'] : "";
    $options['id'] = isset($options['id']) ? $options['id'] : "";
    $args = shortcode_atts(array(
        'domain'                => $options['domain'],
        'id'                  => $options['id'],
    ), $atts);

    $domain = $args['domain'];
    $id = $args['id'];

    return displayKaarten($domain, $id);
}

function array_any(array $array, callable $fn) {
    foreach ($array as $value) {
        if($fn($value)) {
            return true;
        }
    }
    return false;
}


function displayKaarten($domain, $id) {

  $dateFtm = new IntlDateFormatter('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
  $dateFtm->setPattern('EEEE d MMMM yyyy');

  $timeFtm = new IntlDateFormatter('nl_NL', IntlDateFormatter::NONE, IntlDateFormatter::FULL);
  $timeFtm->setPattern('HH:mm');


  $url = $domain ."/api/voorstelling/". $id;

  $voorstelling = fetchVoorstelling($url);
  // filter voorstelling-uitvoeringen waarbij uitvoering.aanvang > vandaag
  $voorstelling->uitvoeringen = array_filter($voorstelling->uitvoeringen, function($uitvoering) {
    return strtotime($uitvoering->aanvang) >= strtotime(date('Y-m-d'));
  });
//  var_dump($voorstelling);
  $displayWachtrij = array_any($voorstelling->uitvoeringen, function($uitvoering) {
    return $uitvoering->vrije_plaatsen <= 2;
  });


 
  ob_start(); ?>
<div class="card kaarten">
  <div class="row">
    <label>Locatie</label>
    <div><?php echo $voorstelling->locatie?></div>
  </div>
  <div class="row">
    <label>Prij<?php echo count($voorstelling->prijzen) > 1  ? "zen" : "s" ?></label>
    <div class="d-flex flex-column">
      <?php foreach ($voorstelling->prijzen as $prijs) { 
        if( $prijs->role == null ) { ?>
      <span><?php echo $prijs->description . " € " .  number_format($prijs->prijs, 2, ",", ".") ?></span>
      <?php }
      } ?>
    </div>
  </div>
  <?php if ($voorstelling->opmerkingen) { ?>
  <div class="row">
    <label>Opmerkingen</label>
    <div><?php echo $voorstelling->opmerkingen?></div>
  </div>
  <?php } ?>

  <?php if ($displayWachtrij) { ?>
  <div class="row">
    <label>Wachtlijst</label>
    <div>
      Aarzel niet om een plaats op de wachtlijst te nemen. <br />
      De ervaring leert dat er op het laatst nog veel plaatsen vrij kunnen
      komen
    </div>
  </div>

  <?php } ?>
  <div class="row">
    <table class="table ml-0">
      <thead>
        <tr>
          <th>datum</th>
          <th></th>
          <th>aanvang</th>
          <th>ontvangst</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($voorstelling->uitvoeringen as $uitvoering) { 
          $aanvang = date_create($uitvoering->aanvang);
          $deur_open = date_create($uitvoering->deur_open);
          ?>
        <tr>
          <td>
            <a
              href="<?php echo $domain?>/voorstelling/<?php echo $voorstelling->id?>/?uitvoering_id=<?php echo $uitvoering->id?>">
              <?php echo $dateFtm->format($aanvang)?>
            </a>
          </td>
          <td><?php echo $uitvoering->extra_text?></td>
          <td><?php echo $timeFtm->format($aanvang)?></td>
          <td><?php echo $timeFtm->format($deur_open)?></td>
          <td><?php echo $uitvoering->status?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <div class="row">
    <a href="<?php echo $domain?>/voorstelling/<?php echo $voorstelling->id?>" class="btn btn-primary">Reserveren
    </a>
  </div>
</div>

<?php
    $html = ob_get_contents();
    ob_end_clean();

  return $html;
}

function fetchVoorstelling($url) {
  $response = wp_remote_get($url);
  $body = wp_remote_retrieve_body($response);
    
  $data = json_decode($body, false);
  
  return $data;
}