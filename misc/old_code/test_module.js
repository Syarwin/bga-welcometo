// on welcometo.js : 
// if (!stop)
//     //     // new testModule().test();
//     new bgagame.testmodule(this).test();
// new bgagame.wtoCardViewer().test();
// this.CardViewer = new bgagame.wtoCardViewer()
// CardViewer().test();

// test: function () {
//     console.log("It works!");
// },

//new testModule().test();
define([
    "dojo", "dojo/_base/declare",
    // g_gamethemeurl + "welcometo.js",
    // "ebg/core/gamegui",
], function (dojo, declare) {
    return declare("bgagame.testmodule", ebg.core.gamegui, {
        constructor: function (gameui) {
            console.log("HI");
            this.welcometo = gameui;
        },

        increment: function () {
            privateValue++;
        },

        decrement: function () {
            privateValue--;
        },

        getValue: function () {
            return privateValue;
        },

        test: function () {
            dojo.addClass('pagemaintitletext', 'test');
            this.addTooltipHtml('pagemaintitletext', "<img class='help_card_tooltip'></img>");
            // new welcometo(true).test();
            this.welcometo.test();
        }
    });
});

//This works with testModule.test()
// define([
//     "dojo", "dojo/_base/declare",
//     g_gamethemeurl + "welcometo.js",
// ], function (dojo, declare, welcometo) {
//     var privateValue = 0;
//     return {
//         increment: function () {
//             privateValue++;
//         },

//         decrement: function () {
//             privateValue--;
//         },

//         getValue: function () {
//             return privateValue;
//         },

//         test: function () {
//             dojo.addClass('pagemaintitletext', 'test');
//         }
//     };
// });

// Notes about wtoCardViewer.js
// define([
//     "dojo", "dojo/_base/declare",
//     "ebg/core/gamegui",
// ], function (dojo, declare) {
//     return declare("bgagame.wtoCardViewer", ebg.core.gamegui, {
//         constructor: function () {
//             console.log("HI from wtoCardViewer");
//         },

//         increment: function () {
//             privateValue++;
//         },

//         decrement: function () {
//             privateValue--;
//         },

//         getValue: function () {
//             return privateValue;
//         },

//         test: function () {
//             dojo.addClass('pagemaintitletext', 'test');
//             this.addTooltipHtml('pagemaintitletext', "<img class='help_card_tooltip'></img>");
//             // new welcometo(true).test();
//         }
//     });
// });