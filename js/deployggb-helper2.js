// JavaScript Document
// v 2022-08-07 20:17

// ######################################################################################################
// keine Ã„nderungen nach dieser Zeile notwendig 
// <script src="/pluginfile.php/1/local_eduvidual/globalfiles/0/_sys/geogebra/deployggb-helper2.js" type="text/javascript"></script>
// ######################################################################################################
showfeedback = false;
GGBTimerActive = false;
GGBTimerID = false;

initdone = false;

function ggbOnInit()
{
    console.log("ggbOnInit " + ggbApplet.getVersion());
    //console.log(stack2ggb);
    //console.log(jQuery);

    // Werte beim Anzeigen nur übergeben, wenn nix gespeichert worden ist
    var setValues = true;

    /*
    try {
      // Feedback anzeigen oder nicht?
      if (document.querySelector(".stackprtfeedback").length != 0) {
        //console.log("CFBB: " + count_feedbackblocks);
        setValues = false;
      }
    } catch (err) {
      console.log("INFO-ERROR stackprtfeedback: " + err);
    }
    */

    // Überprüfen, ob es ein Speicherfeld mit sinnvollem für das Applet gibt
    // wenn ja, dann "Werte-aus-STACK" deaktivieren
    try
    {
        ggbState = document.querySelector("textarea[name*='ans_ggb_base64']")
            .value;
        //console.log("ggbState: " + ggbState);
        //if (!(ggbState == "" || ggbState == undefined)) setValues = true;
        if (!(ggbState == "" || ggbState == undefined || ggbState.length <
                50))
        {
            setValues = false;
            //console.log("setValues = false");
        }
    }
    catch (err)
    {
        console.log("INFO-ERROR ans_ggb_base64: " + err);
    }

    // kein Speicherfeld vorhanden -> "Werte-aus-STACK" ausführen
    if (setValues)
    {
        for (var key in stack2ggb)
        {
            console.log(key + " -> " + stack2ggb[key]);
            try
            {
                ggbApplet.setValue(key, stack2ggb[key]);
                console.log("INFO-stack2ggb: " + key + " -> " + stack2ggb[key]);
            }
            catch (err3)
            {
                console.log("ERROR-stack2ggb: " + key + " " + stack2ggb[key] +
                    " " + err3);
            }
        }
        for (var key in stack2ggb_caption)
        {
            console.log(key + " -> " + stack2ggb_caption[key]);
            try
            {
                ggbApplet.setCaption(key, stack2ggb_caption[key]);
            }
            catch (err3)
            {
                console.log("ERROR-stack2ggb_caption: " + key + " " +
                    stack2ggb_caption[key] + " " + err3);
            }
        }
    }


    try
    {
        ggbApplet.registerAddListener("GGBupdateAnswer");
        ggbApplet.registerUpdateListener("GGBupdateAnswer");

        //überprüfen, ob init schon ausgeführt -> würde sich sonst rekursiv aufrufen
        if (!initdone)
        {
            GGBgetStatefromanswer();
            initdone = true;
        }


        if (showfeedback == true)
        {
            try
            {
                ggbApplet.setValue("showfeedback", true);
            }
            catch (err)
            {
                console.log("INFO-ERROR showfeedback: " + err);
            }


            ggbApplet.unregisterAddListener("GGBupdateAnswer");
            ggbApplet.unregisterUpdateListener("GGBupdateAnswer");
        }

    }
    catch (err)
    {
        console.log("ERROR - ggbOninit - HTML: " + err);
    }

}

var GGBgetStatefromanswer = function ()
{
    //console.log("GGBgetStatefromanswer");
    try
    {
        ggbState = document.querySelector(
            "textarea[name*='ans_ggb_base64']").value;
        //console.log("ggbState: " + ggbState);
        if (!(ggbState == "" || ggbState == undefined || ggbState.length <
                50)) ggbApplet.setBase64(ggbState);
    }
    catch (err)
    {
        console.log("INFO-ERROR ans_ggb_base64: " + err);
    }
    try
    {
        // Feedback anzeigen oder nicht?
        if (document.querySelector(".stackprtfeedback").length != 0)
        {
            //console.log("CFBB: " + count_feedbackblocks);
            showfeedback = true;
        }
    }
    catch (err)
    {
        console.log("INFO-ERROR stackprtfeedback: " + err);
    }

}


var GGBupdateAnswerBase64 = function ()
{
    var ggbState = ggbApplet.getBase64();
    console.log("GGBupdateAnswerBase64");
    //console.log(ggbState);
    document.querySelector("textarea[name*='ans_ggb_base64']").value =
        ggbState;

    // Timer zurücksetzen
    GGBTimerActive = false;
    GGBTimerID = false;

}

// GGBupdateAnswer (BASE64-Teil) immer 1000 Millisekunden verzögert ausführen
var GGBupdateAnswer = function (value)
{
    //console.log("GGBupdateAnswer");

    try
    {
        /*
      var ggbState = ggbApplet.getBase64();
      //console.log(ggbState);
      document.querySelector("textarea[name*='ans_ggb_base64']").value = ggbState;
      */
        if (GGBTimerActive == false)
        {
            GGBTimerActive = true;
            GGBTimerID = window.setTimeout(GGBupdateAnswerBase64, 1000);
        }
    }
    catch (err)
    {
        console.log("ggbState: " + ggbState);
    }

    // Variablen auslesen

    for (var i = 0; i < ggb2stack.length; i++)
    {
        try
        {
            var key = ggb2stack[i];
            var value = ggbApplet.getValue(key);
            var inputname = "'ans_ggbv_" + key + "'";
            //console.log("in: " + inputname);
            document.querySelector("input[name*=" + inputname + "]").value =
                value;

        }
        catch (err1)
        {
            console.log("ggb2stack: " + err1);
        }
    }



}

var applet = new GGBApplet(ggb_parameters, true);

window.addEventListener("load", function (event)
{
    //console.log("DOM fully loaded and parsed");
    try
    {
        if (ggb2stack_version !== undefined)
        {
            //ggb2stack_version = "5.0.574.0";
            applet.setHTML5Codebase("https://www.geogebra.org/apps/" +
                ggb2stack_version + "/web3d");
        }
    }
    catch (err10)
    {}
    applet.inject('applet_container', 'preferHTML5');
});
