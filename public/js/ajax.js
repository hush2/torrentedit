// (C) 2012 hush2 <hushywushy@gmail.com>

window.onload = function()
{
    btn = document.getElementById('scrape');
    btn.onclick = do_xhr;
}

function trim(s) {
  return s.replace(/^(\s|\u00A0)+|(\s|\u00A0)+$/g, '')
}

function ajax(xhr, tracker_url) {

    xhr = false;
    try {
        xhr = new XMLHttpRequest();
    } catch (e) {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    xhr.open('POST', '/scrape');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {

        if (xhr.readyState == 4 && xhr.status == 200) {
            json = eval('('+xhr.responseText+')');

            if (json[info_hash] === false) {
                data = "<span class='error'>error</span>";
            } else {
                data =  "<span class='seeders'>"   + json[info_hash]['seeders']   +"</span> ";
                data += "<span class='leechers'>"  + json[info_hash]['leechers']  +"</span> ";
                data += "<span class='completed'>" + json[info_hash]['completed'] +"</span> ";
            }
            msg = document.getElementById('msg');
            msg.innerHTML += tracker_url + ' ' + data + '<br/>';

            trackers_count -= 1;
            if (trackers_count < 1) {
                ajaximg = document.getElementById('ajaximg');
                ajaximg.style.display = 'none';
            }
        }
  }

  info_hash = document.getElementById('info_hash').value;

  xhr.send('url=' + tracker_url + '&info_hash=' + info_hash);

}

var trackers_count = 0;

function do_xhr() {

    ajaximg = document.getElementById('ajaximg');
    ajaximg.style.display = 'inline';

    desc = document.getElementById('desc');
    desc.style.display = 'block';

    msg.innerHTML = '';

    tracker_list = document.getElementById('trackers').value
    tracker_list = tracker_list.split('\n')
    trackers = []
    for (i = 0; i < tracker_list.length; i++) {
      tracker = trim(tracker_list[i])
      if (!tracker.search(/^(udp|http):\/\//)) {
        trackers.push(tracker_list[i])
      }
    }

    trackers_count = trackers.length;
    xhr = new Array(trackers.length)
    for (i in trackers) {
          ajax(xhr[i], trackers[i])
    }
}

