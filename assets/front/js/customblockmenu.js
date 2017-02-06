$(document).on("click", "#customblockmenu .plus-minus", function() {
	var $sub_menu = $(this).parents(".parent").find(".sub-menu");
	if($sub_menu.is(":hidden")) {
		$sub_menu.slideDown(100);
		$(this).addClass("active");
	} else {
		$sub_menu.slideUp(100);
		$(this).removeClass("active");
	}
});