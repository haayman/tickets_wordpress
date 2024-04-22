async function loadKaarten($, options) {
  let { domId } = options;
  const elem = $(`#${domId}`);
  const params = {
    action: "kaarten",
    ...options,
  };
  if (options.loader) {
    $(elem).append(
      '<div class="overlay"><i class="fas fa-spinner fa-spin" style="font-size: 4em; margin:1em;"></i></div>'
    );
  }
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
