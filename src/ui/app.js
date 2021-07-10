function main() {
    setInterval(() => {
        axios.get('/api/processes')
            .then(resp => {

                // Format desktop/tablet
                let data = '';

                let icon = "<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-7 w-7\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">\
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z\" />\
                    </svg>";

                resp.data.forEach((item, i) => {

                    let logButton = "<div class='w-8 text-gray-400 hover:text-gray-600 block' id=\"hook-"+i+"\" onclick=\"openLogModal('"+item.name+"')\">"+icon+"</div>";

                    data += "<tr class=\"bg-white border border-gray-200 h-12\">";
                    data += "   <td class='truncate md:px-3'>"+ item.name + "</td>";
                    data += "   <td class='hidden lg:block'><pre class=\"truncate\">" + item.command + "</pre></td>";
                    data += "   <td class='w-10 md:pl-4'>"+logButton+"</td>";
                    data += "   <td class='text-center'>" + statusPill(item.status) + "</td>";
                    data += "   <td class='text-center hidden md:block md:py-4'>" + fuzzyTime(item.started_at) + "</td>";
                    data += "   <td class='hidden lg:block -mt-5 lg:mb-2 text-center'>"+formattedAttempt(item)+"</td>";
                    data += "</tr>";
                });

                document.querySelector('#tablecontent').innerHTML = data;

                let mobile = '';

                // Format Mobile
                resp.data.forEach((item,i) => {

                    mobile += "<div class=\"mt-4 bg-white\" onClick=\"openLogModal('"+item.name+"');\">";
                    mobile += '     <div class="flex justify-between">';
                    mobile += '         <h1 class="text-lg pl-2 pt-1 truncate pr-8">'+item.name+'</h1>';
                    mobile += '          <div class="mt-2">'+statusPill(item.status)+'</div>';
                    mobile += '     </div>';
                    // mobile += '<div class="pl-2 pr-2">';
                    // mobile += "     <pre class='truncate'>"+item.command+"</pre>";
                    // mobile += '</div>';
                    mobile += '<div class="flex justify-between p-2">';
                    mobile += ' <span>'+formattedAttempt(item)+"</span>";
                    mobile += ' <span>'+fuzzyTime(item.started_at)+"</span>";
                    mobile += '</div>';
                    mobile += '</div>';
                })

                document.querySelector('#mobile').innerHTML = mobile;
            })
            .catch(e => console.error(e.message));
    },1000);
}

function classes(...items) { return items.join(' ') }

function formattedAttempt(item) {

    if(item.retries===0 && item.currentRetry===0) {
        return '<span class="text-sm text-gray-400"> 0 / 0 </span>';
    }

    let max = (item.retries===-1) ? '&infin;' : item.retries;

    return '<span class="text-sm text-gray-400">'+item.currentRetry+" / "+max+'</span>';
}

function statusPill(label) {

    if (label === "Finished") {
        return "<div class=\"py-1 px-2 md:py-2 md:px-4 shadow-md no-underline rounded-full bg-blue-800 text-white font-sans font-semibold text-xs md:text-sm border-blue btn-primary hover:text-white hover:bg-blue-light focus:outline-none active:shadow-none mr-2\">Finished</div>";
    }

    if (label === "Running") {
        return "<div class=\"py-1 px-2 md:py-2 md:px-4 shadow-md no-underline rounded-full bg-green-600 text-white font-sans font-semibold text-xs md:text-sm border-blue btn-primary hover:text-white hover:bg-blue-light focus:outline-none active:shadow-none mr-2\">Running</div>";
    }

    if (label === "Crashed") {
        return "<div class=\"py-1 px-2 md:py-2 md:px-4 shadow-md no-underline rounded-full bg-red-600 text-white font-sans font-semibold text-sm md:text-sm border-blue btn-primary hover:text-white hover:bg-blue-light focus:outline-none active:shadow-none mr-2\">Crashed</div>";
    }

    if (label === "Created") {
        return "<div class=\"py-1 px-2 md:py-2 md:px-4 shadow-md no-underline rounded-full bg-blue-400 text-white font-sans font-semibold text-sm md:text-sm border-blue btn-primary hover:text-white hover:bg-blue-light focus:outline-none active:shadow-none mr-2\">Created</div>";
    }

    return label;
}

function fuzzyTime(data) {

    // let data = "2021-01-18T20:30:00";
    // const ts = Date.parse(data);
    // console.log(ts,data);

    return '<span class="text-sm text-gray-400">' + timeDifference(data, 'en') + '</span>';
}

function timeDifference(timestamp, locale) {

    const msPerMinute = 60 * 1000;
    const msPerHour = msPerMinute * 60;
    const msPerDay = msPerHour * 24;

    const current = Date.now();
    const elapsed = current - timestamp;

    const rtf = new Intl.RelativeTimeFormat(locale, {numeric: "auto"});

    if (elapsed < msPerMinute) {
        return rtf.format(-Math.floor(elapsed / 1000), 'seconds');
    } else if (elapsed < msPerHour) {
        return rtf.format(-Math.floor(elapsed / msPerMinute), 'minutes');
    } else if (elapsed < msPerDay) {
        return rtf.format(-Math.floor(elapsed / msPerHour), 'hours');
    } else {
        return new Date(timestamp).toLocaleDateString(locale);
    }
}

function openLogModal(title) {
    document.querySelector('#modal-title').innerHTML = "Recent evens for "+title;
    document.querySelector('#modal-content').innerHTML = "";

    document.querySelector('#modal').style.display = "block";

    // Add listener
    logWatcher = setInterval(() => {

        const url = '/api/log/'+title;
        axios.get(url)
            .then(resp => {
                let lines = resp.data.log
                    .map(line => {
                        if(line.msg !== "") {
                            let channel = line.channel.replace('std','');

                            let prefix;
                            if(channel=="out") {
                                prefix = '<span class="text-gray-400">[Out]</span>';
                            } else {
                                prefix = '<span class="text-red-400">[Err]</span>';
                            }

                            return prefix+"&nbsp;<span class='text-white'>"+line.msg+"</span>";

                        } else {
                            return "";
                        }
                    })
                    .filter(line => line!=="")
                    .join('<br />');

                document.querySelector('#modal-content').innerHTML = lines;
            })
            .catch(e => {
                console.error(e);
            })

    },250);
}

function closeLogModal() {

    document.querySelector('#modal').style.display = 'none';

    // Remove Listener
    clearInterval(logWatcher);
}
