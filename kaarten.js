async function loadKaarten($, domain, id, domId) {
  if (!domId) {
    domId = `#kaarten-${id}`;
  }
  const elem = $(domId);
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
