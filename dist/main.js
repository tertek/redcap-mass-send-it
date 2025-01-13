/**
 * Class Definition
 *
 */
class MassSendIt {
    init() {
        console.log("MassSendIt ready!");
    }
}
/**
 * Instantiate the class
 *
 * Publish module object on global object
 * https://stackoverflow.com/a/72374303/3127170
 *
 */
let STPH_MassSendIt = new MassSendIt();
Object.assign(globalThis, { STPH_MassSendIt });
STPH_MassSendIt.init();
export {};
