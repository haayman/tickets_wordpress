async function loadKaarten($, domain, id) {
  const elem = $(`#kaarten_${id}`);
  try {
    const response = await fetch(
      `/wp-admin/admin-ajax.php?action=kaarten&domain=${encodeURIComponent(
        domain
      )}&id=${id}`
    );
    const html = await response.text();
    $(elem).replaceWith(html);
  } catch (e) {
    console.log(e);
  }
}
