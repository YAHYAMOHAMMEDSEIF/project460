/* Huunganisha Idara -> Divisheni (divisheni huchujwa kulingana na idara iliyochaguliwa) */
function linkDept(d, v){
  function fill(){
    v.innerHTML = '';
    if (!d.value){ v.add(new Option('— chagua idara kwanza —', '')); v.disabled = true; return; }
    if ((window.KIT || []).includes(+d.value)){
      v.add(new Option('Hakuna (kitengo maalum)', '')); v.disabled = true; return;
    }
    v.disabled = false;
    v.add(new Option('— chagua divisheni —', ''));
    (window.DIVS || []).filter(x => x.department_id == d.value)
      .forEach(x => v.add(new Option(x.jina, x.id, false, x.id == v.dataset.sel)));
  }
  d.addEventListener('change', () => { v.dataset.sel = ''; fill(); });
  fill();
}
