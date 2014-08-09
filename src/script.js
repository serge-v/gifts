function getXMLHttpRequest() 
{
    if (window.XMLHttpRequest) {
        return new window.XMLHttpRequest;
    }
    else {
        try {
            return new ActiveXObject("MSXML2.XMLHTTP.3.0");
        }
        catch(ex) {
            return null;
        }
    }
}

var req = getXMLHttpRequest();

function onloaded()
{
	var text = document.getElementById('text');
	text.focus();
}


function submit()
{
	var form = document.getElementById('search');
	form.submit();
}

function selector_keydown(event)
{
	var sbox = document.getElementById('suggestbox');
	var text = document.getElementById('text');
	
	if (window.event)
	{
		e = window.event;
	}
	else
	{
		e = event; //FF uses this
	} 
 
	if (e.keyCode == 13)
	{
		submit();
	}
	else if (e.keyCode == 27)
	{
		sbox.style.display = 'none';
		text.focus();
	}
}

function selector_onclick()
{
	var selector = document.getElementById('selector');
	var text = document.getElementById('text');
	var fid = document.getElementById('fid');
	
	if (selector.selectedIndex >= 0)
	{
		text.value = selector.options[selector.selectedIndex].text;
		fid.value = selector.options[selector.selectedIndex].value;
        if (fid.value > 0)
        {
            text.value = '';
        }
		submit();
	}
}

var searchid = '';

function processkey2(event)
{
	var sbox = document.getElementById('suggestbox');
	var selector = document.getElementById('selector');
	var text = document.getElementById('text');
	var fid = document.getElementById('fid');
	
	if(window.event)
	{
		e = window.event;
	}
	else
	{
		e = event; //FF uses this
	} 
 
	if(e.keyCode == 38)
	{
		if (sbox.style.display == '')
		{
			if (selector.selectedIndex == 0)
			{
				selector.selectedIndex = selector.options.length - 1
			}
			else
			{
				selector.selectedIndex--;
			}
			text.value = selector.options[selector.selectedIndex].text;
			fid.value = selector.options[selector.selectedIndex].value;
		}
		return true;
	}
	else if(e.keyCode == 40)
	{
		if (sbox.style.display == '')
		{
			if (selector.selectedIndex < selector.options.length - 1)
			{
				selector.selectedIndex++;
			}
			else
			{
				selector.selectedIndex = 0;
			}
			text.value = selector.options[selector.selectedIndex].text;
			fid.value = selector.options[selector.selectedIndex].value;
		}
		return true;
	}
	
	return false;
}

function processkey(event)
{
	if (processkey2(event))
	{
		return;
	}
	
	var e = document.getElementById('text');
	var sbox = document.getElementById('suggestbox');

	if (e.value.length == 0)
	{
		sbox.style.display = 'none';
		return;
	}
	req.abort();
	req.onreadystatechange = function()
	{
		if (req.readyState == 4)
		{
			if (req.status == 200)
			{
				sbox.innerHTML = req.responseText;
				
				if (req.responseText.length == 0)
				{
					sbox.style.display = 'none';
				}
				else
				{
					sbox.style.display = '';
				}
			}
		}
	}
	var q = 'ac?q=' + e.value;
	req.open('GET', q, true);
	req.send(null);
}
