function login() {
    // implement your own login logic here
}
// Function to calculate train progress and generate progress bar HTML
function createTrainProgressBar(train, routesData) {
    const route = routesData.find(r => r.route === train.line);
    if (!route) return '<p>Route not found</p>';
    const stations = route.stations;
    const allStationKeys = Object.keys(stations).sort((a, b) => {
        const aParts = a.split('_').map(n => parseInt(n) || 0);
        const bParts = b.split('_').map(n => parseInt(n) || 0);
        if (aParts[0] !== bParts[0]) return aParts[0] - bParts[0];
        if (aParts.length === 1 && bParts.length > 1) return -1;
        if (bParts.length === 1 && aParts.length > 1) return 1;
        if (aParts.length > 1 && bParts.length > 1) return aParts[1] - bParts[1];
        return 0;
    });
    const allStations = allStationKeys.map(key => stations[key]);
    const mainStationKeys = allStationKeys.filter(key => !key.includes('_'));
    const mainStations = mainStationKeys.map(key => stations[key]);
    let displayStations = [...mainStations];
    const currentStationIndex = allStations.indexOf(train.current);
    if (currentStationIndex === -1) return '<p>Current station not found</p>';
    let progressPercent;
    
    if (currentStationIndex === 0) {
        progressPercent = 0;
    } else if (currentStationIndex === allStations.length - 1) {
        progressPercent = 100;
    } else {
        let prevMainIndex = -1;
        let nextMainIndex = -1;
        for (let i = 0; i < mainStationKeys.length; i++) {
            const stationIndex = allStations.indexOf(stations[mainStationKeys[i]]);
            if (stationIndex <= currentStationIndex) {
                prevMainIndex = i;
            } else {
                nextMainIndex = i;
                break;
            }
        }
        
        if (prevMainIndex === -1) {
            progressPercent = 0;
        } else if (nextMainIndex === -1) {
            progressPercent = 100;
        } else {
            const prevMainStation = stations[mainStationKeys[prevMainIndex]];
            const nextMainStation = stations[mainStationKeys[nextMainIndex]];
            const prevStationIndex = allStations.indexOf(prevMainStation);
            const nextStationIndex = allStations.indexOf(nextMainStation);
            const segmentLength = nextStationIndex - prevStationIndex;
            const segmentProgress = currentStationIndex - prevStationIndex;
            const segmentPercent = segmentProgress / segmentLength;
            const mainSegmentPercent = 1 / (mainStationKeys.length - 1);
            progressPercent = ((prevMainIndex / (mainStationKeys.length - 1)) + 
                              (segmentPercent * mainSegmentPercent)) * 100;
        }
    }
    
    let html = '<div class="train-progress">';
    
    displayStations.forEach((station, index) => {
        const position = (index / (displayStations.length - 1)) * 100;
        const isPassed = position <= progressPercent;
        const isCurrent = station === train.current;
        
        html += `
            <div class="station-dot ${isPassed ? 'passed' : ''} ${isCurrent ? 'current' : ''}" 
             style="left: ${position}%">
            ${index !== 0 && index !== displayStations.length - 1 ? `<div class="station-label">${station}</div>` : ''}
            </div>
        `;
    });
    
    html += `<div class="progress-bar" style="width: ${progressPercent}%"></div>`;
    
    if (!mainStations.includes(train.current)) {
        if (progressPercent > 0 && progressPercent < 100) {
            html += `
                <div class="current-marker update-blink" style="left: ${progressPercent}%">
                    <div class="current-label">${train.current}</div>
                </div>
            `;
        }
    }
    
    html += '</div>';
    
    return html;
}
let previousDelays = {};
function openInNewTab(url) {
    window.open(url, '_blank').focus();
}

function CarrierCode(carrier) {
    const carrierCodes = {
        'Železničná spoločnosť Slovensko, a.s.': 'ZSSK',
        'Leo Express Slovensko s.r.o.': 'LESK',
        'Leo Express s.r.o.': 'LE',
        'RegioJet a.s.': 'RJSK'
    };
    
    return carrierCodes[carrier] || carrier;
}


function updateTrainTable() {
    if (!window.routesData) {
        $.ajax({
            url: 'routes.json',
            dataType: 'json',
            async: false,
            success: function(data) {
                window.routesData = data;
            },
            error: function() {
                console.error('Error loading routes.json');
            }
        });
    }

    $.ajax({
        url: 'get_train_data.php',
        type: 'GET',
        success: function(data) {
            var trainsContainer = $('#trains');
            trainsContainer.empty();
            const trainTypePriority = {
                'EN': 1,
                'EC': 2,
                'RJ': 3,
                'LE': 4,
                'IC': 5,
                'Ex': 6,
                'R': 7,
                'REX': 8,
                'Zr': 9,
                'Os': 10
            };

            const carrierLogos = {
                'Železničná spoločnosť Slovensko, a.s.': 'clogo/zssk.png',
                'Leo Express Slovensko s.r.o.': 'clogo/leoexpress.png',
                'Leo Express s.r.o.': 'clogo/leoexpress.png',
                'RegioJet a.s.': 'clogo/regiojet.png',
                'Default': 'clogo/default_logo.png'
            };

            data.sort(function(a, b) {
                const priorityA = trainTypePriority[a.type] || 10;
                const priorityB = trainTypePriority[b.type] || 10;
                return priorityA - priorityB;
            });

            data.forEach(function(train) {
                var trainDiv = $('<div></div>')
                    .attr('id', train.train_number)
                    .addClass('train');

                const carrierLogo = carrierLogos[train.carrier] || carrierLogos['Default'];

                const trainTypeColors = {
                    'EN': 'dark_green',
                    'EC': 'green',
                    'RJ': '#c8c800',
                    'LE': 'lime',
                    'IC': 'green',
                    'Ex': 'dark_orange',
                    'R': 'red',
                    'REX': 'orange',
                    'Zr': 'teal',
                    'Os': 'gray'
                };

                var trainNumberEl = $('<p></p>')
                    .addClass('train_number')
                    .html(`
                        <img src="${carrierLogo}" alt="${train.carrier}" style="width: 20px; height: 20px; margin-right: 5px; vertical-align: middle;">
                        ${train.type} ${train.train_number} ${train.train_name}
                    `);

                if (trainTypeColors[train.type]) {
                    trainNumberEl.css('color', trainTypeColors[train.type]);
                    trainNumberEl.css('font-weight', 'bold');
                }

                trainDiv.append(trainNumberEl);

                var detailsButton = $('<button></button>')
                    .addClass('details-button')
                    .html('<i class="fas fa-train"></i>')
                    .css({
                        'position': 'absolute',
                        'right': 0,
                        'margin-right': '24px',
                        'padding': '5px 10px',
                        'background-color': '#007bff',
                        'color': 'white',
                        'border': 'none',
                        'border-radius': '4px',
                        'cursor': 'pointer'
                    })
                    .on('click', function() {
                        openInNewTab(`https://www.vagonweb.cz/razeni/vlak.php?zeme=${CarrierCode(train.carrier)}&cislo=${train.train_number}&rok=2025`);
                    });

                trainNumberEl.append(detailsButton);

                var delayText = $('<p></p>').addClass('train_delay');
                var currentDelay = train.delay;
                var previousDelay = previousDelays[train.train_number];

                var delayDisplay = $('<span></span>').text(
                    currentDelay == 0 ? "No delay" : 
                    currentDelay + ' ' + (currentDelay == 1 ? 'minute' : 'minutes')
                );

                if (typeof previousDelay !== 'undefined' && currentDelay !== previousDelay) {
                    const diff = currentDelay - previousDelay;
                    const arrow = diff > 0 ? '↑' : '↓';
                    const color = diff > 0 ? '#ff4444' : '#44ff44';

                    $('<span></span>')
                        .addClass('delay-change')
                        .text(arrow + Math.abs(diff))
                        .css('color', color)
                        .appendTo(delayDisplay);
                }

                if (currentDelay < 0) {
                    delayDisplay.css('color', 'lightblue');
                } else if (currentDelay <= 5) {
                    delayDisplay.css('color', 'green');
                } else if (currentDelay <= 15) {
                    delayDisplay.css('color', 'orange');
                } else {
                    delayDisplay.css('color', 'red');
                }

                delayText.append(delayDisplay);
                trainDiv.append(delayText);

                previousDelays[train.train_number] = currentDelay;

                trainDiv.append($('<p></p>').addClass('train_from').text(train.from));
                trainDiv.append($('<p></p>').addClass('train_to').text(train.to));

                if (window.routesData && train.line && train.current) {
                    var progressHtml = createTrainProgressBar(train, window.routesData);
                    trainDiv.append($('<div></div>').addClass('train_line').html(progressHtml));
                } else {
                    trainDiv.append($('<p></p>').addClass('train_line').css('font-size', '6px').css('text-align', 'center').text('Tracking not available'));
                }

                trainsContainer.append(trainDiv);
                trainsContainer.append('<br>');
            });
        },
        error: function() {
            console.error('Error fetching train data');
        }
    });
}


setInterval(updateTrainTable, 3000);

updateTrainTable();
