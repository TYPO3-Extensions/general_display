var tx_generaldatadisplay_pi1 = {
	input_by_select: function(selectID,inputID){
        	selectval = document.getElementById(selectID).value;
		document.getElementById(inputID).value = selectval;
	},

	add_option: function(selectID,key,value,is_selected) {
		var select = document.getElementById(selectID);
		var option = document.createElement('option');
		option.setAttribute('value', this.escapeHtml(value)); 
                option.innerHTML = this.escapeHtml(key);
                if (is_selected) option.selected = true;
                select.appendChild(option); 
	},

	create_ranged_select: function(selectID,from,to,selected){
		var select = document.getElementById(selectID);
		var match = false;
		for (var i=from; i <=to; i++) {
			var index = (i<10) ? '0'+i : i;
			if (selected) {
				selected = Number(selected);
			}
          		var option = document.createElement('option');
                	option.setAttribute('value', String(index));
                	option.innerHTML = String(index);
			if (option.value == selected) {
				match = true;
                                option.selected = true;
                        }
                	select.appendChild(option);
        	}
		if (selected && !match) {
			this.add_option(selectID,selected,selected,true);
		}
	},
	
	toggle_visibility: function(selectID){
  		var element = document.getElementById(selectID);
		if (element.style.display == 'inline')
          		element.style.display = 'none';
       		else element.style.display = 'inline';
	},

	toggle_link_title: function(selectID,symbol1,symbol2) {
		var element = document.getElementById(selectID);
		var symbol = (element.innerHTML == symbol1) ? symbol2 : symbol1;
		element.innerHTML = symbol;
	},
	
	escapeHtml: function(html) {
		html = String(html);
		return html
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}
};

