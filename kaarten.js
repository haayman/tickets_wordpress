async function loadKaarten($, options) {
  let { domId } = options;
  const elem = $(`#${domId}`);
  const params = {
    action: "kaarten",
    ...options,
  };
  const searchParams = new URLSearchParams(params);
  try {
    const response = await fetch(
      `/wp-admin/admin-ajax.php?${searchParams.toString()}`
    );
    const html = await response.text();
    $(elem).replaceWith(html);
  } catch (e) {
    console.log(e);
  }
}
