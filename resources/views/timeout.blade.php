setInterval(() => { if (new Date() - larapoke_date >= {{ $interval }} + {{ $lifetime }}) { location.reload(true); }},2000);