/*
document.addEventListener("deviceready", function() {
    document.addEventListener("resume", function() {
        require(["block_eduvidual/main"], function(MAIN) {MAIN.resume();});
    }, false);
}, false);
*/
function handleOpenURL(urlstr) {
    console.log('handle: ' + urlstr);
    var urlo = new URL(urlstr);
    var userid = +urlo.searchParams.get("userid");
    var token = urlo.searchParams.get("token");
    console.log(userid,token);
    if (userid > 0 && token != '') {
        localStorage.setItem('block_eduvidual_userid', userid);
        localStorage.setItem('block_eduvidual_token', token);
        require(["block_eduvidual/main"], function(MAIN) {MAIN.resume();});
    }
}
