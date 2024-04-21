<?php
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('kaarten', plugin_dir_url(__FILE__)  . 'kaarten.css');
  wp_enqueue_script('kaarten', plugin_dir_url(__FILE__)  . 'kaarten.js', ['jquery']);
});

$domIdCounter = 0;


function kaarten_shortcode($atts)
{
  global $domIdCounter;

  $options['domain'] = isset($options['domain']) ? $options['domain'] : "";
  $options['id'] = isset($options['id']) ? $options['id'] : "";
  $args = shortcode_atts(array(
    'domain'                => $options['domain'],
    'id'                  => $options['id'],
  ), $atts);

  $domain = $args['domain'];
  $id = $args['id'];
  $domId = "kaarten-" . ++$domIdCounter;
  $back = !$id;


  $script = <<<EOT
<script>
jQuery(document).ready(function($) {

  loadKaarten($, {domain:'{$domain}', id:'{$id}', domId:'{$domId}', back:'{$back}'});

});
</script>
EOT;


  if ($id) {

    return kaartenDiv(['domain' => $domain, 'id' => $id, 'domId' => $domId]) . $script;
  } else {
    return kaartenList(['domain' => $domain, 'domId' => $domId]);
  }
}

function array_any(array $array, callable $fn)
{
  foreach ($array as $value) {
    if ($fn($value)) {
      return true;
    }
  }
  return false;
}



function kaartenList($options)
{
  $domain = $options['domain'];
  $domId = $options['domId'];

  $url = $domain . "/api/voorstelling";
  $voorstellingen = fetchVoorstelling($url);
  if (!$voorstellingen || count($voorstellingen) == 0) {
    return "<div class='alert alert-danger'>Er zijn momenteel geen voorstellingen</div>";
  } else if (count($voorstellingen) == 1) {
    return kaartenDiv($domain, $voorstellingen[0]->id);
  }

  $ids = array_map(function ($voorstelling) {
    return $voorstelling->id;
  }, $voorstellingen);
  ob_start(); ?>
<div class="card kaarten" id="<?php echo $domId ?>">
  <div class="row">
    Kies een voorstelling
  </div>
  <div class="row">
    <?php foreach ($voorstellingen as $voorstelling) { ?>
    <a class="col btn"
      onclick="loadKaarten(jQuery, {domain:'<?php echo $domain ?>', id: <?php echo $voorstelling->id ?>, domId:'<?php echo $domId ?>', 'back':true})">
      <img src="<?php echo $voorstelling->thumbnail ?>" height="100"><br />
      <?php echo $voorstelling->title ?>
    </a>
    <?php } ?>
  </div>
</div>
<?php

  $html = ob_get_contents();
  ob_end_clean();

  return $html;
}


function kaartenDiv($options)
{
  $domain = $options['domain'];
  $id = $options['id'];
  $back = $options['back'];
  $domId = $options['domId'];

  $url = $domain . "/api/voorstelling/" . $id;
  try {

    $timezone = new DateTimeZone('Europe/Amsterdam');

    $dateFtm = new IntlDateFormatter('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
    $dateFtm->setPattern('EEEE d MMMM yyyy');

    $timeFtm = new IntlDateFormatter('nl_NL', IntlDateFormatter::NONE, IntlDateFormatter::FULL, "Europe/Amsterdam");
    $timeFtm->setPattern('HH:mm');


    $url = $domain . "/api/voorstelling/" . $id;

    $voorstelling = fetchVoorstelling($url);
    if ($voorstelling == null) {
      return "<div class='alert alert-danger'>Er is een fout opgetreden bij het ophalen van de kaarten</div>";
    }
    // filter voorstelling-uitvoeringen waarbij uitvoering.aanvang > vandaag
    $voorstelling->uitvoeringen = array_filter($voorstelling->uitvoeringen, function ($uitvoering) {
      return strtotime($uitvoering->aanvang) >= strtotime(date('Y-m-d'));
    });
    // var_dump($voorstelling);
    $displayWachtrij = array_any($voorstelling->uitvoeringen, function ($uitvoering) {
      return $uitvoering->vrije_plaatsen <= 2;
    });
    ob_start(); ?>
<div class="card kaarten" id="<?php echo $domId ?>">
  <div class="row">
    <label>Locatie</label>
    <div><?php echo $voorstelling->locatie ?></div>
  </div>
  <div class="row">
    <label>Prij<?php echo count($voorstelling->prijzen) > 1  ? "zen" : "s" ?></label>
    <div style="display: flex; flex-direction: column; flex-wrap: wrap;">
      <?php foreach ($voorstelling->prijzen as $prijs) {
            if ($prijs->role == null) { ?>
      <span><?php echo $prijs->description . " € " .  number_format($prijs->prijs, 2, ",", ".") ?></span>
      <?php }
          } ?>
    </div>
  </div>
  <?php if ($voorstelling->opmerkingen) { ?>
  <div class="row">
    <label>Opmerkingen</label>
    <div><?php echo $voorstelling->opmerkingen ?></div>
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
              $aanvang = date_create($uitvoering->aanvang,  $timezone);
              $deur_open = date_create($uitvoering->deur_open, $timezone);
            ?>
        <tr>
          <td>
            <a
              href="<?php echo $domain ?>/voorstelling/<?php echo $voorstelling->id ?>/?uitvoering_id=<?php echo $uitvoering->id ?>">
              <?php echo $dateFtm->format($aanvang) ?>
            </a>
          </td>
          <td><?php echo $uitvoering->extra_text ?></td>
          <td><?php echo $timeFtm->format($aanvang) ?></td>
          <td><?php echo $timeFtm->format($deur_open) ?></td>
          <td><?php echo $uitvoering->status ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <div class="row">
    <div class="col">
      <a href="<?php echo $domain ?>/voorstelling/<?php echo $voorstelling->id ?>" class="btn btn-primary">Bestellen</a>
    </div>
    <?php if ($back) { ?>
    <div class="col">
      <a class="btn"
        onclick="loadKaarten(jQuery, {domain:'<?php echo $domain ?>', domId:'<?php echo $domId ?>'})">Terug</a>
    </div>
    <?php } ?>
    </a>
  </div>
</div>

<?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  } catch (Exception $e) {
    ob_end_clean();
    return "<div class='alert alert-danger'>Er is een fout opgetreden bij het ophalen van de kaarten</div>";
  }
}

function fetchVoorstelling($url)
{
  try {
    $response = wp_remote_get($url);
    $body = wp_remote_retrieve_body($response);

    $data = json_decode($body, false);

    return $data;
  } catch (Exception $e) {
    return null;
  }
}

$ajax = function () {
  $domain = $_GET['domain'];
  $id = $_GET['id'];
  $domId = $_GET['domId'];
  $back = $_GET['back'];
  try {
    if (!$id) {
      $html = kaartenList(['domain' => $domain, 'domId' => $domId]);
    } else {
      $html = kaartenDiv(['domain' => $domain, 'id' => $id, 'domId' => $domId, 'back' => $back]);
    }
    echo $html;
    wp_die(); // prevent '0' in output
  } catch (Exception $e) {
    var_dump($e);
    header('', true, 500);
    echo $e->getMessage();
  }
};

add_action('wp_ajax_kaarten', $ajax);
add_action('wp_ajax_nopriv_kaarten', $ajax);