// add first block
$(document).on("click", "#custom_block_menu_form .add-first-block", function() {
	var data = {
		data: {action: 'add', type: 'first'},
		success: function(res) {
			$(".add-first-block").remove();
			$(".form-wrapper").html(res.data);
		}
	};

	return update_row(data);
});

// add parent block
$(document).on("click", "#custom_block_menu_form .add-block", function() {
	var data = {
		data: {action: 'add', type: 'parent'},
		success: function(res) {
			$(".form-wrapper").html(res.data);
		}
	};

	return update_row(data);
});

// add child block
$(document).on("click", "#custom_block_menu_form .add-child-block", function() {

	var $parent = $(this).parents(".block"),
		id = +$parent.attr('data-id'),
		data = {
			data: {action: 'add', type: 'child', 'id': id},
			success: function(res) {
				$parent.find(".child-block-area").html(res.data);
			}
		};

	return update_row(data);
});

// delete parent block
$(document).on("click", "#custom_block_menu_form .delete-block", function() {

	if(!confirm(CBM.delete_block))
		return false;

	var id = +$(this).parents(".block").attr('data-id'),
		data = {
			data: {action: 'delete', type: 'parent', 'id': id},
			success: function(res) {
				$(".form-wrapper").html(res.data);
				
				var arr = [];

				$(".form-wrapper .block").each(function(i) {
					arr.push(i);
				});

				if(arr.length < 1) {
					$(".form-wrapper").html("<button type='button' class='btn btn-success add-first-block'>"+CBM.add_block+"</button>");
				}
			}
		};

	return update_row(data);
});

// delete child block
$(document).on("click", "#custom_block_menu_form .delete-child-block", function() {

	if(!confirm(CBM.delete_block))
		return false;

	var $parent = $(this).parents(".block"),
		parent = +$parent.attr('data-id'),
		id = +$(this).parents(".child-block").attr('data-child-id'),
		data = {
			data: {action: 'delete', type: 'child', 'id': id, 'parent': parent},
			success: function(res) {
				$parent.find(".child-block-area").html(res.data);
			}
		},
		arr = [];

	$parent.find(".child-block").each(function(i) {
		arr.push(i);
	});

	if(arr < 2) {
		$parent.find(".is_parent").click();
		return true;
	}

	return update_row(data);
});

// toggle block parent or not
$(document).on("change", "#custom_block_menu_form .is_parent", function() {

	var val = $(this).is(":checked"),
		$parent = $(this).parents(".block"),
		id = +$parent.attr('data-id'),
		data = {
			data: {action: '', type: 'child', id: id},
			success: function(res) {
				$parent.find(".child-block-area").html(res.data);
			}
		};
	
	if(val === true) {
		data.data.action = 'add';
	} else {
		if(!confirm(CBM.delete_all_children)) {
			$(this).prop("checked", "checked");
			return false;
		}

		data.data.action = 'delete-all-childs';
	}

	return update_row(data);
});

// toggle block parent or not
$(document).on("change", "#custom_block_menu_form input[type='text']", function() {
	return update_values(false);
});

// toggle block parent or not
$(document).on("click", "#custom_block_menu_form #module_form_submit_btn", function() {
	return update_values(true);
});

// save input values
var update_values = function(save_button) {

	var data = {
		data: $("#custom_block_menu_form").serialize(),
		beforeSend: false,
		success: function(res) {
			if(save_button && save_button === true)
				alert(CBM.saved);
		}
	};
	
	return update_row(data);
};

// insert row via ajax
var update_row = function(data) {
	$.ajax({
		url: '/modules/customblockmenu/inc/handler.php',
		type: "POST",
		data: data.data,
		beforeSend: function() {
			if(data.beforeSend !== false)
				$(".form-wrapper").addClass("loading"); 
		},
		success: function(res) {
			if(res.status === 200) {
				$(".form-wrapper").removeClass("loading"); 
				return data.success(res);
			}
		},
		error: function(res) {
		},
	})
}