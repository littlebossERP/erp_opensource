if (typeof Ensogo === 'undefined') Ensogo = new Object();

Ensogo = {
	existingImages: '',
	selColorArr: [],
	selSizeArr: [],
	selSites: [],
	sale_type: '',
	isClick: false,
	sites: [],
	otherColorDataB: ["fawn", "frenchlilac", "darkraspberry", "pinklavender", "copperpenny", "brightnavyblue", "chamoisee", "paleplum", "forestcamouflage", "olive", "darkseagreen", "frenchbeige", "metallicbronze", "rosetaupe", "darkchestnut", "orangepeel", "desertsand", "lightkhaki", "palebrown", "mintcream", "grape", "gold", "fashionfuchsia", "mountainmeadow", "dutchwhite", "fushcia", "uclablue", "lapislazuli", "telemagenta", "chinapink", "neonred", "illuminatingemerald", "rhythm", "darkspringgreen", "crimson", "islamicgreen", "regalia", "pastelred", "greyishwhite", "darklavender", "brown", "green-yellow", "imperialred", "barnred", "mattewhite", "outrageousorange", "carrotorange", "veronica", "fluorescentorange", "darktaupe", "portlandorange", "tangerineyellow", "cyan", "bubbles", "cobalt", "peridot", "richelectricblue", "skyblue", "frenchskyblue", "littleboyblue", "brinkpink", "asparagus", "darkgray", "mediumspringbud", "moccasin", "platinum", "lightpink", "sand", "caribbeangreen", "red", "northtexasgreen", "purpletaupe", "bulgarianrose", "olivine", "claret", "purplefuchsia", "japaneseindigo", "steelblue-purple", "chinarose", "lightgoldenrodyellow", "almond", "deepcoffee", "lavender", "wildblueyonder", "neongreen", "wheat", "stildegrainyellow", "blue", "yelloworange", "newcar", "magnolia", "zebraprint", "sangria", "burgundy", "purplishred", "rossocorsa", "chromeyellow", "carolinablue", "pinksherbet", "rustbrown", "frenchlime", "kucrimson", "deepcerise", "deepskyblue", "sunglow", "pucered", "darkvanilla", "nadeshikopink", "brightcerulean", "yankeesblue", "kenyancopper", "jasmine", "mountbattenpink", "lemonlime", "red-brown", "russianviolet", "ufogreen", "oldmauve", "roseebony", "cadmiumgreen", "electricviolet", "flavescent", "purplemountainmajesty", "floralwhite", "yellowcamouflage", "vividviolet", "flamingopink", "metallicsilver", "azuremist", "gray-blue", "cinnamon", "antiquebronze", "lightredochre", "palespringbud", "sacramentostategreen", "deepmossgreen", "mughalgreen", "lemoncurry", "golden", "rubyred", "spartancrimson", "greenplaid", "straw", "frenchbistre", "lightseagreen", "purpleheart", "off-white", "sienna", "vividcerise", "skyblueorange", "purplepizzazz", "rawumber", "cameopink", "rosewood", "gamboge", "copperred", "ebony", "lime", "whitedyedip", "princetonorange", "shockingpink", "bluesapphire", "winered", "royalblue", "lakeblue", "lavendermagenta", "tuscan", "pearl", "capri", "frenchblue", "freshair", "palecarmine", "blueberry", "cerise", "chestnut", "blue-gray", "brightgreen", "lightyellow", "mikadoyellow", "pastelviolet", "tangerine", "flax", "lemonchiffon", "chocolate", "melon", "cordovan", "aquablue", "darkgrey", "psychedelicpurple", "catawba", "mediumcarmine", "fandangopink", "darktangerine", "silvergreen", "antiquewhite", "tiger'seye", "paletaupe", "baker-millerpink", "patriarch", "darkslateblue", "lightskyblue", "sandstorm", "rufous", "lumber", "languidlavender", "deeppink", "fandango", "pumpkin", "plum", "palered-violet", "bittersweet", "timberwolf", "paleturquoise", "lightbrown", "dodgerblue", "honolulublue", "liverchestnut", "navypurple", "magenta", "tan", "mediumruby", "pink", "purplelight", "vividauburn", "blackfuchsia", "safetyyellow", "pistachio", "persimmon", "jellybean", "khakigreen", "orange-red", "mellowapricot", "giantsorange", "mangotango", "cardinal", "rose", "cheetah", "palecerulean", "midnightblue", "richcarmine", "coralpink", "purplenavy", "yellowrose", "darkjunglegreen", "transparentwhite", "brightube", "mordantred19", "non-photoblue", "darkliver", "fuchsia-black", "lightslategray", "operamauve", "floral", "magentahaze", "japaneseviolet", "camelbrown", "rubinered", "mediumseagreen", "lightgray", "smoke", "heliotrope", "tractorred", "cadetblue", "bluebell", "ultramarineblue", "mayablue", "silverwhite", "ruddybrown", "ticklemepink", "linen", "lightleopard", "green", "lighttan", "powderblue", "bluebonnet", "tiffanyblue", "peachpuff", "cherrypink", "wine", "babypink", "kombugreen", "mediumviolet-red", "tuscantan", "darkcoral", "smokyblack", "nacarat", "yellow-green", "italianskyblue", "usccardinal", "violet-red", "sandytaupe", "electricindigo", "cambridgeblue", "khakibeige", "lavendermist", "darkimperialblue", "shamrockgreen", "indigodye", "razzmatazz", "malachite", "screamin'green", "silverpink", "sealbrown", "mediumblue", "electriclime", "orange", "lincolngreen", "lasallegreen", "canaryyellow", "antiqueblack", "smalt", "coffee", "lemonyellow", "aurometalsaurus", "brandeisblue", "pinkpearl", "englishviolet", "mossgreen", "upmaroon", "pastelorange", "dollarbill", "pink-orange", "winedregs", "persianrose", "wildorchid", "fuchsiarose", "jonquil", "pastelyellow", "cocoa", "candypink", "goldenrod", "catalinablue", "uablue", "razzmicberry", "tiger", "sunset", "brightmaroon", "dukeblue", "ecru", "deeptaupe", "radicalred", "safetyorange", "wildwatermelon", "darkmagenta", "applegreen", "utahcrimson", "oldmossgreen", "bone", "brightred", "hansayellow", "tumbleweed", "pastelblue", "lilac", "reddevil", "mahogany", "orangeplaid", "genericviridian", "mediumturquoise", "multicolored", "mediumtaupe", "zebra", "queenblue", "navy", "cadmiumyellow", "trolleygrey", "oceanboatblue", "sandy", "celestialblue", "olivegreen", "darkarmygreen", "darkplum", "navajowhite", "deeptuscanred", "milkwhite", "richlavender", "huntergreen", "lightpurple", "darkkhaki", "palatinatepurple", "seablue", "stizza", "peach-yellow", "amaranth", "amethyst", "steelpink", "uscgold", "lightcarminepink", "cornsilk", "independence", "ruddy", "slategray", "ferrarired", "donkeybrown", "acidgreen", "dirt", "leopard", "bistre", "paoloveronesegreen", "crimsonglory", "steelblue", "gray-asparagus", "magicmint", "rosepink", "carnelian", "aliceblue", "copperrose", "schausspink", "gainsboro", "waterblue", "riflegreen", "feldgrau", "phthalogreen", "palepink", "inkblue", "lightgrey", "lightcyan", "alizarincrimson", "darksalmon", "rocketmetallic", "bleudefrance", "lavenderpurple", "blush", "mediumjunglegreen", "teagreen", "burntorange", "copper", "puce", "unmellowyellow", "licorice", "limerick", "tuftsblue", "coolgrey", "mistyrose", "upforestgreen", "kobi", "ambergold", "electricgreen", "darksienna", "kobe", "eminence", "imperial", "glitter", "skymagenta", "darkyellow", "lavenderindigo", "hollywoodcerise", "jet", "springbud", "burlywood", "red-violet", "terracotta", "carminepink", "richblack", "electricultramarine", "camouflagegreen", "laserlemon", "spanishorange", "periwinkle", "cinereous", "purpureus", "transparentred", "darkskyblue", "tomato", "darktan", "cadmiumred", "laurelgreen", "neonfuchsia", "heathergray", "palechestnut", "englishlavender", "denimblue", "greenapple", "greenlight", "lightsalmon", "richmaroon", "flattery", "darkelectricblue", "iceberg", "hotpink", "raspberry", "limegreen", "darkpastelgreen", "pear", "peacockblue", "celadon", "darkscarlet", "blue-violet", "flame", "fireenginered", "goldfusion", "lightsalmonpink", "goldenyellow", "coconut", "persianblue", "titaniumyellow", "androidgreen", "washedblack", "pansypurple", "olivegreenblack", "ginger", "payne'sgrey", "lightgreen", "persianplum", "blond", "cgblue", "romansilver", "mint", "pastelbrown", "byzantine", "whitesmoke", "lightblue", "violet-blue", "richbrilliantlavender", "fluorescentrose", "raspberryrose", "tulip", "deepmagenta", "vegasgold", "snow", "camouflage", "tropicalrainforest", "mediumelectricblue", "bananayellow", "rajah", "zomp", "fluorescentpink", "raspberrypink", "unbleachedsilk", "soap", "transparent", "yellowlight", "silverchalice", "lemonglacier", "oldsilver", "salmon", "antiquesilver", "harvestgold", "mediumskyblue", "airforceblue", "smokeytopaz", "ruddypink", "alloyorange", "fielddrab", "pictorialcarmine", "clear", "imperialpurple", "mulberry", "blackbean", "americanrose", "schoolbusyellow", "deepcarmine", "mediumtuscanred", "brickred", "lavenderblue", "sanddune", "darkpastelblue", "silvergray", "cyberyellow", "asphalt", "burntumber", "sheengreen", "neonorange", "lightapricot", "palemagenta", "blackolive", "glaucous", "oldburgundy", "persianred", "dogwoodrose", "liberty", "coralgreen", "pearlypurple", "mintgreen", "sapgreen", "britishracinggreen", "cornellred", "deepchampagne", "pinkbeige", "rackley", "blue-cream", "papayawhip", "spacecadet", "black", "heathergrey", "carmine", "persianpink", "silversand", "firebrick", "pastelgreen", "viridiangreen", "indianred", "lightmediumorchid", "lightbeige", "dandelion", "mauvetaupe", "transparentdarkgreen", "ceil", "darkblue-gray", "amazon", "zaffre", "atomictangerine", "bistrebrown", "ceruleanfrost", "auburn", "gradientblack", "tuscanred", "anti-flashwhite", "gray", "darkturquoise", "champagne", "paleblue", "checkeredwhite", "coquelicot", "diamond", "darkbyzantium", "slateblue", "topaz", "pastelpink", "alabamacrimson", "xanadu", "midori", "falured", "arcticblue", "folly", "columbiablue", "tangelo", "debianred", "jade", "yaleblue", "skobeloff", "grey", "lion", "darkcandyapplered", "thistle", "violet", "silverlakeblue", "outerspace", "hanblue", "spanishblue", "persiangreen", "budgreen", "honeydew", "cornflowerblue", "classicrose", "strawberry", "darkblue", "pinkindigo", "carnationpink", "venetianred", "icterine", "babyblue", "raspberryglace", "mediumred-violet", "prune", "oucrimsonred", "richlilac", "darkcoffee", "barbiepink", "acidblue", "darkcyan", "tealblue", "mediumlavendermagenta", "sapphire", "tangopink", "brown-cream", "feldspar", "ashblack", "white", "darkred", "grannysmithapple", "zinnwalditebrown", "umber", "brilliantrose", "bronze", "pastelmagenta", "antiquefuchsia", "spanishcrimson", "darkterracotta", "wenge", "sunsetorange", "bottlegreen", "candyapplered", "aqua", "englishgreen", "persianorange", "parisgreen", "keppel", "multicolor", "queenpink", "berry", "cybergrape", "darkpastelpurple", "darkpink", "lightsteelblue", "spanishgray", "frenchraspberry", "armygreen", "imperialblue", "duststorm", "darkcerulean", "leopardprint", "avocado", "celeste", "olivedrab", "peru", "rosevale", "otterbrown", "superpink", "lightfuchsiapink", "stormcloud", "arsenic", "cosmiclatte", "royalfuchsia", "turquoisegreen", "brightturquoise", "rosebonbon", "deepruby", "fulvous", "vermilion", "purewhite", "indiagreen", "cadmiumorange", "spanishbistre", "darkpowderblue", "vividorchid", "uclagold", "aquamarine", "neonpink", "moonstoneblue", "caputmortuum", "amaranthpink", "robineggblue", "bananamania", "indianyellow", "mediumchampagne", "royalpurple", "deer", "aero", "onyx", "rosered", "lawngreen", "thulianpink", "turquoise-purple", "cottoncandy", "phlox", "liver", "upsdellred", "taupegray", "congopink", "ruby", "offwhite", "mediumpersianblue", "oldlavender", "red-orange", "hotmagenta", "scarlet", "etonblue", "pastelpurple", "vanilla", "blue-green", "cherryblossompink", "sunray", "fallow", "neonblue", "celadongreen", "taupe", "brightlavender", "newspaper", "officegreen", "fluorescentgreen", "deeplemon", "russiangreen", "lighttaupe", "cheddaryellow", "cheetahprint", "palecopper", "wildstrawberry", "shampoo", "darkgoldenrod", "tealdeer", "lust", "maygreen", "sandybrown", "mantis", "beaver", "windsortan", "frenchrose", "lightorchid", "heartgold", "ceruleanblue", "lightcornflowerblue", "deepfuchsia", "englishred", "darkorange", "yellow", "eggshell", "lava", "darkbrown", "naplesyellow", "toolbox", "rosybrown", "matteblack", "resolutionblue", "brightpink", "egyptianblue", "deepspacesparkle", "palegoldenrod", "whitegold", "cocoabrown", "carminered", "castletongreen", "mardigras", "vividburgundy", "bitterlemon", "antiquebrass", "springgreen", "lightwinered", "orchidpink", "tealgreen", "electriccyan", "lemon", "lemonmeringue", "purple-cream", "sonicsilver", "phthaloblue", "paleviolet-red", "eucalyptus", "tuscany", "battleshipgrey", "lavenderblush", "frenchwine", "waterspout", "cadetgrey", "electriclavender", "champagne-orange", "royalyellow", "palelavender", "napiergreen", "royalazure", "sinopia", "rosequartz", "seagreen", "turquoiseblack", "snowwhite", "mediumpurple", "harlequin", "indigo", "camel", "emerald", "checkeredred", "sepia", "darkolivegreen", "saffron", "lavenderrose", "tuscanbrown", "celerygreen", "harvardcrimson", "satinsheengold", "fuchsiapink", "oriolesorange", "floralprint", "bondiblue", "aureolin", "cherry", "b'dazzledblue", "mediumorchid", "bittersweetshimmer", "ultrapink", "silver", "grullo", "chartreuse", "mediumslateblue", "deepsaffron", "modebeige", "lightcrimson", "neonyellow", "denim", "peach-orange", "alaskablue", "turquoise", "persianindigo", "turkishrose", "lavendergray", "silvergrey", "mauvelous", "ultramarine", "sapphireblue", "manatee", "livid", "mediumvermilion", "oldrose", "uared", "shadow", "boysenberry", "deeplilac", "pearlaqua", "midnightgreen", "desire", "trueblue", "frenchpuce", "darkgreen", "cgred", "electriccrimson", "viridian", "nude", "darkviolet", "saddlebrown", "tigerprint", "darkpurple", "tyrianpurple", "shimmeringblush", "crystalclear", "spanishskyblue", "buff", "goldenbrown", "blackleatherjacket", "eggplant", "burntsienna", "babypowder", "tearose", "lakegreen", "mauve", "metallicsunburst", "guppiegreen", "palerobineggblue", "earthyellow", "majorelleblue", "metallicgold", "hanpurple", "irresistible", "rosemadder", "deepjunglegreen", "frenchmauve", "purple", "cerisepink", "darkmossgreen", "smitten", "warmblack", "salmonpink", "popstar", "pinklace", "piggypink", "bisque", "kellygreen", "palegreen", "babyblueeyes", "khaki", "bole", "electricpurple", "bluewash", "corn", "razzledazzlerose", "byzantium", "wisteria", "fluorescencegreen", "jazzberryjam", "vividskyblue", "mexicanpink", "ferngreen", "warmyellow", "bazaar", "ochre", "metallicseaweed", "iris", "cedarchest", "coralred", "blueyonder", "ube", "coral", "neoncarrot", "electricyellow", "cinnabar", "orangelight", "palegold", "calpolygreen", "beige", "ashgrey", "seashell", "azure", "oceanblue", "oldlace", "amber", "cadet", "citron", "charmpink", "rustyred", "unitednationsblue", "drab", "bitterlime", "lightrose", "antiqueruby", "jasper", "lightcoral", "deepchestnut", "rosegold", "nyanza", "maize", "chocolatebrown", "fluorescentblue", "paleaqua", "coolblack", "mediumspringgreen", "usafablue", "fuchsia", "blanchedalmond", "dartmouthgreen", "bigdipo'ruby", "deepcarminepink", "citrine", "goldenpoppy", "electricblue", "irishgreen", "spanishcarmine", "ivory", "oxfordblue", "brunswickgreen", "palecornflowerblue", "pinklight", "spanishviridian", "starcommandblue", "lightthulianpink", "forestgreen", "darkpastelred", "palesilver", "aeroblue", "lightpastelpurple", "selectiveyellow", "ruber", "woodbrown", "apricot", "brightblue", "daffodil", "junglegreen", "flirt", "brilliantlavender", "bubblegumpink", "pastelgray", "oldgold", "cerulean", "antiquegold", "darkslategray", "rust", "turquoiseblue", "prussianblue", "russet", "msugreen", "charcoal", "pinegreen", "warmwhite", "bronzeyellow", "maroon", "deepmauve", "lavenderpink", "blizzardblue", "africanpurple", "deeppeach", "meatbrown", "charlestongreen", "isabelline", "blast-offbronze", "newyorkpink", "transparentpink", "cream", "abyss", "redblack", "fuzzywuzzy", "darkmidnightblue", "inchworm", "peach", "ballblue", "dimgray", "redwood", "bubblegum", "blackgray", "junebud", "mediumaquamarine", "chineseviolet", "palatinateblue", "beaublue", "orchid", "watermelonred", "peakgreen", "urobilin", "waterwavecamouflage", "quartz", "mustard", "darkorchid", "teal", "chinesered", "ghostwhite", "mellowyellow", "deepcarrotorange", "lightcoffee", "vanillaice", "grassgreen", "celadonblue", "bluelightwash", "brass", "fluorescentyellow", "desert", "myrtlegreen", "pakistangreen", "darklava", "vividtangerine", "verdigris", "spirodiscoball", "brown-nose", "navyblue", "lightmossgreen", "arylideyellow", "twilightlavender", "davy'sgrey", "mediumcandyapplered", "blackwhite"],
	otherColorDataA: ["Fawn", "French lilac", "Dark raspberry", "Pink lavender", "Copper penny", "Bright navy blue", "Chamoisee", "Pale plum", "Forest camouflage", "Olive", "Dark sea green", "French beige", "Metallic Bronze", "Rose taupe", "Dark chestnut", "Orange peel", "Desert sand", "Light khaki", "Pale brown", "Mint cream", "Grape", "Gold", "Fashion fuchsia", "Mountain Meadow", "Dutch white", "Fushcia", "UCLA Blue", "Lapis lazuli", "Telemagenta", "China pink", "Neon red", "Illuminating Emerald", "Rhythm", "Dark spring green", "Crimson", "Islamic green", "Regalia", "Pastel red", "Greyish white", "Dark lavender", "Brown", "Green-yellow", "Imperial red", "Barn red", "Matte White", "Outrageous Orange", "Carrot orange", "Veronica", "Fluorescent orange", "Dark taupe", "Portland Orange", "Tangerine yellow", "Cyan", "Bubbles", "Cobalt", "Peridot", "Rich electric blue", "Sky blue", "French sky blue", "Little boy blue", "Brink pink", "Asparagus", "Dark gray", "Medium spring bud", "Moccasin", "Platinum", "Light pink", "Sand", "Caribbean green", "Red", "North Texas Green", "Purple taupe", "Bulgarian rose", "Olivine", "Claret", "Purple fuchsia", "Japanese indigo", "Steel Blue-Purple", "China rose", "Light goldenrod yellow", "Almond", "Deep coffee", "Lavender", "Wild blue yonder", "Neon green", "Wheat", "Stil de grain yellow", "Blue", "Yellow Orange", "New Car", "Magnolia", "Zebra print", "Sangria", "Burgundy", "Purplish red", "Rosso corsa", "Chrome yellow", "Carolina blue", "Pink Sherbet", "Rust brown", "French lime", "KU Crimson", "Deep cerise", "Deep sky blue", "Sunglow", "Puce red", "Dark vanilla", "Nadeshiko pink", "Bright cerulean", "Yankees blue", "Kenyan copper", "Jasmine", "Mountbatten pink", "Lemon lime", "Red-brown", "Russian violet", "UFO Green", "Old mauve", "Rose ebony", "Cadmium green", "Electric violet", "Flavescent", "Purple mountain majesty", "Floral white", "Yellow camouflage", "Vivid violet", "Flamingo pink", "Metallic Silver", "Azure mist", "Gray-blue", "Cinnamon", "Antique bronze", "Light red ochre", "Pale spring bud", "Sacramento State green", "Deep moss green", "Mughal green", "Lemon curry", "Golden", "Ruby red", "Spartan Crimson", "Green plaid", "Straw", "French bistre", "Light sea green", "Purple Heart", "Off-white", "Sienna", "Vivid cerise", "Skyblue Orange", "Purple pizzazz", "Raw umber", "Cameo pink", "Rosewood", "Gamboge", "Copper red", "Ebony", "Lime", "White Dye Dip", "Princeton orange", "Shocking pink", "Blue sapphire", "Wine red", "Royal blue", "Lake blue", "Lavender magenta", "Tuscan", "Pearl", "Capri", "French blue", "Fresh Air", "Pale carmine", "Blueberry", "Cerise", "Chestnut", "Blue-gray", "Bright green", "Light yellow", "Mikado yellow", "Pastel violet", "Tangerine", "Flax", "Lemon chiffon", "Chocolate", "Melon", "Cordovan", "Aqua blue", "Dark grey", "Psychedelic purple", "Catawba", "Medium carmine", "Fandango pink", "Dark tangerine", "Silver green", "Antique white", "Tiger's eye", "Pale taupe", "Baker-Miller pink", "Patriarch", "Dark slate blue", "Light sky blue", "Sandstorm", "Rufous", "Lumber", "Languid lavender", "Deep pink", "Fandango", "Pumpkin", "Plum", "Pale red-violet", "Bittersweet", "Timberwolf", "Pale turquoise", "Light brown", "Dodger blue", "Honolulu blue", "Liver chestnut", "Navy purple", "Magenta", "Tan", "Medium ruby", "Pink", "Purple light", "Vivid auburn", "Black fuchsia", "Safety yellow", "Pistachio", "Persimmon", "Jelly Bean", "Khaki Green", "Orange-red", "Mellow apricot", "Giants orange", "Mango Tango", "Cardinal", "Rose", "Cheetah", "Pale cerulean", "Midnight blue", "Rich carmine", "Coral pink", "Purple navy", "Yellow rose", "Dark jungle green", "Transparent White", "Bright ube", "Mordant red 19", "Non-photo blue", "Dark liver", "Fuchsia-Black", "Light slate gray", "Opera mauve", "Floral", "Magenta haze", "Japanese violet", "Camel brown", "Rubine red", "Medium sea green", "Light gray", "Smoke", "Heliotrope", "Tractor red", "Cadet blue", "Blue Bell", "Ultramarine blue", "Maya blue", "Silver white", "Ruddy brown", "Tickle Me Pink", "Linen", "Light leopard", "Green", "Light tan", "Powder blue", "Bluebonnet", "Tiffany Blue", "Peach puff", "Cherry Pink", "Wine", "Baby pink", "Kombu green", "Medium violet-red", "Tuscan tan", "Dark coral", "Smoky black", "Nacarat", "Yellow-green", "Italian sky blue", "USC Cardinal", "Violet-red", "Sandy taupe", "Electric indigo", "Cambridge Blue", "Khaki Beige", "Lavender mist", "Dark imperial blue", "Shamrock green", "Indigo dye", "Razzmatazz", "Malachite", "Screamin' Green", "Silver pink", "Seal brown", "Medium blue", "Electric lime", "Orange", "Lincoln green", "La Salle Green", "Canary yellow", "Antique Black", "Smalt", "Coffee", "Lemon yellow", "AuroMetalSaurus", "Brandeis blue", "Pink pearl", "English violet", "Moss green", "UP Maroon", "Pastel orange", "Dollar bill", "Pink-orange", "Wine dregs", "Persian rose", "Wild orchid", "Fuchsia rose", "Jonquil", "Pastel yellow", "Cocoa", "Candy pink", "Goldenrod", "Catalina blue", "UA blue", "Razzmic Berry", "Tiger", "Sunset", "Bright maroon", "Duke blue", "Ecru", "Deep Taupe", "Radical Red", "Safety orange", "Wild Watermelon", "Dark magenta", "Apple green", "Utah Crimson", "Old moss green", "Bone", "Bright red", "Hansa yellow", "Tumbleweed", "Pastel blue", "Lilac", "Red devil", "Mahogany", "Orange plaid", "Generic viridian", "Medium turquoise", "Multicolored", "Medium taupe", "Zebra", "Queen blue", "Navy", "Cadmium yellow", "Trolley Grey", "Ocean Boat Blue", "Sandy", "Celestial blue", "Olive green", "Dark army green", "Dark plum", "Navajo white", "Deep Tuscan red", "Milk white", "Rich lavender", "Hunter green", "Light purple", "Dark khaki", "Palatinate purple", "Sea blue", "Stizza", "Peach-yellow", "Amaranth", "Amethyst", "Steel pink", "USC Gold", "Light carmine pink", "Cornsilk", "Independence", "Ruddy", "Slate gray", "Ferrari Red", "Donkey Brown", "Acid green", "Dirt", "Leopard", "Bistre", "Paolo Veronese green", "Crimson glory", "Steel blue", "Gray-asparagus", "Magic mint", "Rose pink", "Carnelian", "Alice blue", "Copper rose", "Schauss pink", "Gainsboro", "Water blue", "Rifle green", "Feldgrau", "Phthalo green", "Pale pink", "Ink blue", "Light grey", "Light cyan", "Alizarin crimson", "Dark salmon", "Rocket metallic", "Bleu de France", "Lavender purple", "Blush", "Medium jungle green", "Tea green", "Burnt orange", "Copper", "Puce", "Unmellow yellow", "Licorice", "Limerick", "Tufts Blue", "Cool grey", "Misty rose", "UP Forest green", "Kobi", "Amber gold", "Electric green", "Dark sienna", "Kobe", "Eminence", "Imperial", "Glitter", "Sky magenta", "Dark yellow", "Lavender indigo", "Hollywood cerise", "Jet", "Spring bud", "Burlywood", "Red-violet", "Terra cotta", "Carmine pink", "Rich black", "Electric ultramarine", "Camouflage green", "Laser Lemon", "Spanish orange", "Periwinkle", "Cinereous", "Purpureus", "Transparent red", "Dark sky blue", "Tomato", "Dark tan", "Cadmium red", "Laurel green", "Neon fuchsia", "Heather Gray", "Pale chestnut", "English lavender", "Denim blue", "Green Apple", "Green light", "Light salmon", "Rich maroon", "Flattery", "Dark electric blue", "Iceberg", "Hot pink", "Raspberry", "Lime green", "Dark pastel green", "Pear", "Peacock blue", "Celadon", "Dark scarlet", "Blue-violet", "Flame", "Fire engine red", "Gold Fusion", "Light salmon pink", "Golden yellow", "Coconut", "Persian blue", "Titanium yellow", "Android green", "Washed black", "Pansy purple", "Olive green black", "Ginger", "Payne's grey", "Light green", "Persian plum", "Blond", "CG Blue", "Roman silver", "Mint", "Pastel brown", "Byzantine", "White smoke", "Light blue", "Violet-blue", "Rich brilliant lavender", "Fluorescent rose", "Raspberry rose", "Tulip", "Deep magenta", "Vegas gold", "Snow", "Camouflage", "Tropical rain forest", "Medium electric blue", "Banana yellow", "Rajah", "Zomp", "Fluorescent pink", "Raspberry pink", "Unbleached silk", "Soap", "Transparent", "Yellow light", "Silver chalice", "Lemon glacier", "Old silver", "Salmon", "Antique silver", "Harvest gold", "Medium sky blue", "Air Force blue", "Smokey topaz", "Ruddy pink", "Alloy orange", "Field drab", "Pictorial carmine", "Clear", "Imperial purple", "Mulberry", "Black bean", "American rose", "School bus yellow", "Deep carmine", "Medium Tuscan red", "Brick red", "Lavender blue", "Sand dune", "Dark pastel blue", "Silver gray", "Cyber yellow", "Asphalt", "Burnt umber", "Sheen Green", "Neon orange", "Light apricot", "Pale magenta", "Black olive", "Glaucous", "Old burgundy", "Persian red", "Dogwood rose", "Liberty", "Coral green", "Pearly purple", "Mint green", "Sap green", "British racing green", "Cornell Red", "Deep champagne", "Pink Beige", "Rackley", "Blue-Cream", "Papaya whip", "Space cadet", "Black", "Heather grey", "Carmine", "Persian pink", "Silver sand", "Firebrick", "Pastel green", "Viridian green", "Indian red", "Light medium orchid", "Light beige", "Dandelion", "Mauve taupe", "Transparent dark green", "Ceil", "Dark blue-gray", "Amazon", "Zaffre", "Atomic tangerine", "Bistre brown", "Cerulean frost", "Auburn", "Gradient black", "Tuscan red", "Anti-flash white", "Gray", "Dark turquoise", "Champagne", "Pale blue", "Checkered White", "Coquelicot", "Diamond", "Dark byzantium", "Slate blue", "Topaz", "Pastel pink", "Alabama crimson", "Xanadu", "Midori", "Falu red", "Arctic blue", "Folly", "Columbia blue", "Tangelo", "Debian red", "Jade", "Yale Blue", "Skobeloff", "Grey", "Lion", "Dark candy apple red", "Thistle", "Violet", "Silver Lake blue", "Outer Space", "Han blue", "Spanish blue", "Persian green", "Bud green", "Honeydew", "Cornflower blue", "Classic rose", "Strawberry", "Dark blue", "Pink indigo", "Carnation pink", "Venetian red", "Icterine", "Baby blue", "Raspberry glace", "Medium red-violet", "Prune", "OU Crimson Red", "Rich lilac", "Dark coffee", "Barbie pink", "Acid blue", "Dark cyan", "Teal blue", "Medium lavender magenta", "Sapphire", "Tango pink", "Brown-Cream", "Feldspar", "Ash black", "White", "Dark red", "Granny Smith Apple", "Zinnwaldite brown", "Umber", "Brilliant rose", "Bronze", "Pastel magenta", "Antique fuchsia", "Spanish crimson", "Dark terra cotta", "Wenge", "Sunset orange", "Bottle green", "Candy apple red", "Aqua", "English green", "Persian orange", "Paris Green", "Keppel", "Multicolor", "Queen pink", "Berry", "Cyber grape", "Dark pastel purple", "Dark pink", "Light steel blue", "Spanish gray", "French raspberry", "Army green", "Imperial blue", "Dust storm", "Dark cerulean", "Leopard print", "Avocado", "Celeste", "Olive Drab", "Peru", "Rose vale", "Otter brown", "Super pink", "Light fuchsia pink", "Stormcloud", "Arsenic", "Cosmic latte", "Royal fuchsia", "Turquoise green", "Bright turquoise", "Rose bonbon", "Deep ruby", "Fulvous", "Vermilion", "Pure white", "India green", "Cadmium orange", "Spanish bistre", "Dark powder blue", "Vivid orchid", "UCLA Gold", "Aquamarine", "Neon pink", "Moonstone blue", "Caput mortuum", "Amaranth pink", "Robin egg blue", "Banana Mania", "Indian yellow", "Medium champagne", "Royal purple", "Deer", "Aero", "Onyx", "Rose red", "Lawn green", "Thulian pink", "Turquoise-Purple", "Cotton candy", "Phlox", "Liver", "Upsdell red", "Taupe gray", "Congo pink", "Ruby", "Off white", "Medium Persian blue", "Old lavender", "Red-orange", "Hot magenta", "Scarlet", "Eton blue", "Pastel purple", "Vanilla", "Blue-green", "Cherry blossom pink", "Sunray", "Fallow", "Neon blue", "Celadon green", "Taupe", "Bright lavender", "Newspaper", "Office green", "Fluorescent green", "Deep lemon", "Russian green", "Light taupe", "Cheddar Yellow", "Cheetah print", "Pale copper", "Wild Strawberry", "Shampoo", "Dark goldenrod", "Teal deer", "Lust", "May green", "Sandy brown", "Mantis", "Beaver", "Windsor tan", "French rose", "Light orchid", "Heart Gold", "Cerulean blue", "Light cornflower blue", "Deep fuchsia", "English red", "Dark orange", "Yellow", "Eggshell", "Lava", "Dark brown", "Naples yellow", "Toolbox", "Rosy brown", "Matte Black", "Resolution blue", "Bright pink", "Egyptian blue", "Deep Space Sparkle", "Pale goldenrod", "White gold", "Cocoa brown", "Carmine red", "Castleton green", "Mardi Gras", "Vivid burgundy", "Bitter lemon", "Antique brass", "Spring green", "Light wine red", "Orchid pink", "Teal green", "Electric cyan", "Lemon", "Lemon meringue", "Purple-Cream", "Sonic silver", "Phthalo blue", "Pale violet-red", "Eucalyptus", "Tuscany", "Battleship grey", "Lavender blush", "French wine", "Waterspout", "Cadet grey", "Electric lavender", "Champagne-Orange", "Royal yellow", "Pale lavender", "Napier green", "Royal azure", "Sinopia", "Rose quartz", "Sea green", "Turquoise black", "Snow White", "Medium purple", "Harlequin", "Indigo", "Camel", "Emerald", "Checkered Red", "Sepia", "Dark olive green", "Saffron", "Lavender rose", "Tuscan brown", "Celery green", "Harvard crimson", "Satin sheen gold", "Fuchsia pink", "Orioles orange", "Floral print", "Bondi blue", "Aureolin", "Cherry", "B'dazzled blue", "Medium orchid", "Bittersweet shimmer", "Ultra pink", "Silver", "Grullo", "Chartreuse", "Medium slate blue", "Deep saffron", "Mode beige", "Light crimson", "Neon yellow", "Denim", "Peach-orange", "Alaska blue", "Turquoise", "Persian indigo", "Turkish rose", "Lavender gray", "Silver grey", "Mauvelous", "Ultramarine", "Sapphire blue", "Manatee", "Livid", "Medium vermilion", "Old rose", "UA red", "Shadow", "Boysenberry", "Deep lilac", "Pearl Aqua", "Midnight green", "Desire", "True Blue", "French puce", "Dark green", "CG Red", "Electric crimson", "Viridian", "Nude", "Dark violet", "Saddle brown", "Tiger print", "Dark purple", "Tyrian purple", "Shimmering Blush", "Crystal Clear", "Spanish sky blue", "Buff", "Golden brown", "Black leather jacket", "Eggplant", "Burnt sienna", "Baby powder", "Tea rose", "Lake green", "Mauve", "Metallic Sunburst", "Guppie green", "Pale robin egg blue", "Earth yellow", "Majorelle Blue", "Metallic Gold", "Han purple", "Irresistible", "Rose madder", "Deep jungle green", "French mauve", "Purple", "Cerise pink", "Dark moss green", "Smitten", "Warm black", "Salmon pink", "Popstar", "Pink lace", "Piggy pink", "Bisque", "Kelly green", "Pale green", "Baby blue eyes", "Khaki", "Bole", "Electric purple", "Blue Wash", "Corn", "Razzle dazzle rose", "Byzantium", "Wisteria", "Fluorescence green", "Jazzberry jam", "Vivid sky blue", "Mexican pink", "Fern green", "Warm Yellow", "Bazaar", "Ochre", "Metallic Seaweed", "Iris", "Cedar Chest", "Coral red", "Blue yonder", "Ube", "Coral", "Neon Carrot", "Electric yellow", "Cinnabar", "Orange light", "Pale gold", "Cal Poly green", "Beige", "Ash grey", "Seashell", "Azure", "Ocean blue", "Old lace", "Amber", "Cadet", "Citron", "Charm pink", "Rusty red", "United Nations blue", "Drab", "Bitter lime", "Light rose", "Antique ruby", "Jasper", "Light coral", "Deep chestnut", "Rose gold", "Nyanza", "Maize", "Chocolate brown", "Fluorescent blue", "Pale aqua", "Cool black", "Medium spring green", "USAFA blue", "Fuchsia", "Blanched almond", "Dartmouth green", "Big dip o'ruby", "Deep carmine pink", "Citrine", "Golden poppy", "Electric blue", "Irish green", "Spanish carmine", "Ivory", "Oxford Blue", "Brunswick green", "Pale cornflower blue", "Pink light", "Spanish viridian", "Star command blue", "Light Thulian pink", "Forest green", "Dark pastel red", "Pale silver", "Aero blue", "Light pastel purple", "Selective yellow", "Ruber", "Wood brown", "Apricot", "Bright blue", "Daffodil", "Jungle green", "Flirt", "Brilliant lavender", "Bubblegum Pink", "Pastel gray", "Old gold", "Cerulean", "Antique Gold", "Dark slate gray", "Rust", "Turquoise blue", "Prussian blue", "Russet", "MSU Green", "Charcoal", "Pine green", "Warm white", "Bronze Yellow", "Maroon", "Deep mauve", "Lavender pink", "Blizzard Blue", "African purple", "Deep peach", "Meat brown", "Charleston green", "Isabelline", "Blast-off bronze", "New York pink", "Transparent pink", "Cream", "Abyss", "Red black", "Fuzzy Wuzzy", "Dark midnight blue", "Inchworm", "Peach", "Ball blue", "Dim gray", "Redwood", "Bubble gum", "Black gray", "June bud", "Medium aquamarine", "Chinese violet", "Palatinate blue", "Beau blue", "Orchid", "Watermelon red", "Peak green", "Urobilin", "Water wave camouflage", "Quartz", "Mustard", "Dark orchid", "Teal", "Chinese red", "Ghost white", "Mellow yellow", "Deep carrot orange", "Light coffee", "Vanilla ice", "Grass green", "Celadon blue", "Blue Light Wash", "Brass", "Fluorescent yellow", "Desert", "Myrtle green", "Pakistan green", "Dark lava", "Vivid tangerine", "Verdigris", "Spiro Disco Ball", "Brown-nose", "Navy blue", "Light moss green", "Arylide yellow", "Twilight lavender", "Davy's grey", "Medium candy apple red", "Black white"],
	removeBtn: '<button type="button" class="btn btn-default" style="width:64px;height:24px;font:12px/13px Mircsoft Yahei" onclick="Ensogo.goodsRemove(this)" >移除</button>',
	memoryData: [],
	existingVarianceList: '',
	init: function() {
		//color
		var newColor = [];
		var newSize = [];
		var goodsColorData = [{
			name: "白色",
			rgb: "#ffffff",
			colorId: "White",
			class: "fBlack"
		}, {
			name: "黑色",
			rgb: "#000000",
			colorId: "Black"
		}, {
			name: "红色",
			rgb: "#FF2600",
			colorId: "Red"
		}, {
			name: "蓝色",
			rgb: "#0433FF",
			colorId: "Blue"
		}, {
			name: "绿色",
			rgb: "#009051",
			colorId: "Green"
		}, {
			name: "灰色",
			rgb: "#797979",
			colorId: "Grey"
		}, {
			name: "棕色",
			rgb: "#941100",
			colorId: "Brown"
		}, {
			name: "黄褐",
			rgb: "#929000",
			colorId: "Tan"
		}, {
			name: "米黄",
			rgb: "#FFFFCC",
			colorId: "Beige",
			class: "fBlack"
		}, {
			name: "粉红",
			rgb: "#FF2F92",
			colorId: "Pink"
		}, {
			name: "橙色",
			rgb: "#FF9300",
			colorId: "Orange"
		}, {
			name: "黄色",
			rgb: "#FFFB00",
			colorId: "Yellow",
			class: "fBlack"
		}, {
			name: "乳白",
			rgb: "#EBEBEB",
			colorId: "Ivory",
			class: "fBlack"
		}, {
			name: "墨绿",
			rgb: "#005493",
			colorId: "Jasper"
		}, {
			name: "紫色",
			rgb: "#531B93",
			colorId: "Purple"
		}, {
			name: "金色",
			rgb: "#FFD479",
			colorId: "Gold"
		}, {
			name: "多彩",
			rgb: "url('/images/multicolor2.png') repeat",
			colorId: "Multicolor"
		}];
		//其他颜色
		var otherColorDataB = ["fawn", "frenchlilac", "darkraspberry", "pinklavender", "copperpenny", "brightnavyblue", "chamoisee", "paleplum", "forestcamouflage", "olive", "darkseagreen", "frenchbeige", "metallicbronze", "rosetaupe", "darkchestnut", "orangepeel", "desertsand", "lightkhaki", "palebrown", "mintcream", "grape", "gold", "fashionfuchsia", "mountainmeadow", "dutchwhite", "fushcia", "uclablue", "lapislazuli", "telemagenta", "chinapink", "neonred", "illuminatingemerald", "rhythm", "darkspringgreen", "crimson", "islamicgreen", "regalia", "pastelred", "greyishwhite", "darklavender", "brown", "green-yellow", "imperialred", "barnred", "mattewhite", "outrageousorange", "carrotorange", "veronica", "fluorescentorange", "darktaupe", "portlandorange", "tangerineyellow", "cyan", "bubbles", "cobalt", "peridot", "richelectricblue", "skyblue", "frenchskyblue", "littleboyblue", "brinkpink", "asparagus", "darkgray", "mediumspringbud", "moccasin", "platinum", "lightpink", "sand", "caribbeangreen", "red", "northtexasgreen", "purpletaupe", "bulgarianrose", "olivine", "claret", "purplefuchsia", "japaneseindigo", "steelblue-purple", "chinarose", "lightgoldenrodyellow", "almond", "deepcoffee", "lavender", "wildblueyonder", "neongreen", "wheat", "stildegrainyellow", "blue", "yelloworange", "newcar", "magnolia", "zebraprint", "sangria", "burgundy", "purplishred", "rossocorsa", "chromeyellow", "carolinablue", "pinksherbet", "rustbrown", "frenchlime", "kucrimson", "deepcerise", "deepskyblue", "sunglow", "pucered", "darkvanilla", "nadeshikopink", "brightcerulean", "yankeesblue", "kenyancopper", "jasmine", "mountbattenpink", "lemonlime", "red-brown", "russianviolet", "ufogreen", "oldmauve", "roseebony", "cadmiumgreen", "electricviolet", "flavescent", "purplemountainmajesty", "floralwhite", "yellowcamouflage", "vividviolet", "flamingopink", "metallicsilver", "azuremist", "gray-blue", "cinnamon", "antiquebronze", "lightredochre", "palespringbud", "sacramentostategreen", "deepmossgreen", "mughalgreen", "lemoncurry", "golden", "rubyred", "spartancrimson", "greenplaid", "straw", "frenchbistre", "lightseagreen", "purpleheart", "off-white", "sienna", "vividcerise", "skyblueorange", "purplepizzazz", "rawumber", "cameopink", "rosewood", "gamboge", "copperred", "ebony", "lime", "whitedyedip", "princetonorange", "shockingpink", "bluesapphire", "winered", "royalblue", "lakeblue", "lavendermagenta", "tuscan", "pearl", "capri", "frenchblue", "freshair", "palecarmine", "blueberry", "cerise", "chestnut", "blue-gray", "brightgreen", "lightyellow", "mikadoyellow", "pastelviolet", "tangerine", "flax", "lemonchiffon", "chocolate", "melon", "cordovan", "aquablue", "darkgrey", "psychedelicpurple", "catawba", "mediumcarmine", "fandangopink", "darktangerine", "silvergreen", "antiquewhite", "tiger'seye", "paletaupe", "baker-millerpink", "patriarch", "darkslateblue", "lightskyblue", "sandstorm", "rufous", "lumber", "languidlavender", "deeppink", "fandango", "pumpkin", "plum", "palered-violet", "bittersweet", "timberwolf", "paleturquoise", "lightbrown", "dodgerblue", "honolulublue", "liverchestnut", "navypurple", "magenta", "tan", "mediumruby", "pink", "purplelight", "vividauburn", "blackfuchsia", "safetyyellow", "pistachio", "persimmon", "jellybean", "khakigreen", "orange-red", "mellowapricot", "giantsorange", "mangotango", "cardinal", "rose", "cheetah", "palecerulean", "midnightblue", "richcarmine", "coralpink", "purplenavy", "yellowrose", "darkjunglegreen", "transparentwhite", "brightube", "mordantred19", "non-photoblue", "darkliver", "fuchsia-black", "lightslategray", "operamauve", "floral", "magentahaze", "japaneseviolet", "camelbrown", "rubinered", "mediumseagreen", "lightgray", "smoke", "heliotrope", "tractorred", "cadetblue", "bluebell", "ultramarineblue", "mayablue", "silverwhite", "ruddybrown", "ticklemepink", "linen", "lightleopard", "green", "lighttan", "powderblue", "bluebonnet", "tiffanyblue", "peachpuff", "cherrypink", "wine", "babypink", "kombugreen", "mediumviolet-red", "tuscantan", "darkcoral", "smokyblack", "nacarat", "yellow-green", "italianskyblue", "usccardinal", "violet-red", "sandytaupe", "electricindigo", "cambridgeblue", "khakibeige", "lavendermist", "darkimperialblue", "shamrockgreen", "indigodye", "razzmatazz", "malachite", "screamin'green", "silverpink", "sealbrown", "mediumblue", "electriclime", "orange", "lincolngreen", "lasallegreen", "canaryyellow", "antiqueblack", "smalt", "coffee", "lemonyellow", "aurometalsaurus", "brandeisblue", "pinkpearl", "englishviolet", "mossgreen", "upmaroon", "pastelorange", "dollarbill", "pink-orange", "winedregs", "persianrose", "wildorchid", "fuchsiarose", "jonquil", "pastelyellow", "cocoa", "candypink", "goldenrod", "catalinablue", "uablue", "razzmicberry", "tiger", "sunset", "brightmaroon", "dukeblue", "ecru", "deeptaupe", "radicalred", "safetyorange", "wildwatermelon", "darkmagenta", "applegreen", "utahcrimson", "oldmossgreen", "bone", "brightred", "hansayellow", "tumbleweed", "pastelblue", "lilac", "reddevil", "mahogany", "orangeplaid", "genericviridian", "mediumturquoise", "multicolored", "mediumtaupe", "zebra", "queenblue", "navy", "cadmiumyellow", "trolleygrey", "oceanboatblue", "sandy", "celestialblue", "olivegreen", "darkarmygreen", "darkplum", "navajowhite", "deeptuscanred", "milkwhite", "richlavender", "huntergreen", "lightpurple", "darkkhaki", "palatinatepurple", "seablue", "stizza", "peach-yellow", "amaranth", "amethyst", "steelpink", "uscgold", "lightcarminepink", "cornsilk", "independence", "ruddy", "slategray", "ferrarired", "donkeybrown", "acidgreen", "dirt", "leopard", "bistre", "paoloveronesegreen", "crimsonglory", "steelblue", "gray-asparagus", "magicmint", "rosepink", "carnelian", "aliceblue", "copperrose", "schausspink", "gainsboro", "waterblue", "riflegreen", "feldgrau", "phthalogreen", "palepink", "inkblue", "lightgrey", "lightcyan", "alizarincrimson", "darksalmon", "rocketmetallic", "bleudefrance", "lavenderpurple", "blush", "mediumjunglegreen", "teagreen", "burntorange", "copper", "puce", "unmellowyellow", "licorice", "limerick", "tuftsblue", "coolgrey", "mistyrose", "upforestgreen", "kobi", "ambergold", "electricgreen", "darksienna", "kobe", "eminence", "imperial", "glitter", "skymagenta", "darkyellow", "lavenderindigo", "hollywoodcerise", "jet", "springbud", "burlywood", "red-violet", "terracotta", "carminepink", "richblack", "electricultramarine", "camouflagegreen", "laserlemon", "spanishorange", "periwinkle", "cinereous", "purpureus", "transparentred", "darkskyblue", "tomato", "darktan", "cadmiumred", "laurelgreen", "neonfuchsia", "heathergray", "palechestnut", "englishlavender", "denimblue", "greenapple", "greenlight", "lightsalmon", "richmaroon", "flattery", "darkelectricblue", "iceberg", "hotpink", "raspberry", "limegreen", "darkpastelgreen", "pear", "peacockblue", "celadon", "darkscarlet", "blue-violet", "flame", "fireenginered", "goldfusion", "lightsalmonpink", "goldenyellow", "coconut", "persianblue", "titaniumyellow", "androidgreen", "washedblack", "pansypurple", "olivegreenblack", "ginger", "payne'sgrey", "lightgreen", "persianplum", "blond", "cgblue", "romansilver", "mint", "pastelbrown", "byzantine", "whitesmoke", "lightblue", "violet-blue", "richbrilliantlavender", "fluorescentrose", "raspberryrose", "tulip", "deepmagenta", "vegasgold", "snow", "camouflage", "tropicalrainforest", "mediumelectricblue", "bananayellow", "rajah", "zomp", "fluorescentpink", "raspberrypink", "unbleachedsilk", "soap", "transparent", "yellowlight", "silverchalice", "lemonglacier", "oldsilver", "salmon", "antiquesilver", "harvestgold", "mediumskyblue", "airforceblue", "smokeytopaz", "ruddypink", "alloyorange", "fielddrab", "pictorialcarmine", "clear", "imperialpurple", "mulberry", "blackbean", "americanrose", "schoolbusyellow", "deepcarmine", "mediumtuscanred", "brickred", "lavenderblue", "sanddune", "darkpastelblue", "silvergray", "cyberyellow", "asphalt", "burntumber", "sheengreen", "neonorange", "lightapricot", "palemagenta", "blackolive", "glaucous", "oldburgundy", "persianred", "dogwoodrose", "liberty", "coralgreen", "pearlypurple", "mintgreen", "sapgreen", "britishracinggreen", "cornellred", "deepchampagne", "pinkbeige", "rackley", "blue-cream", "papayawhip", "spacecadet", "black", "heathergrey", "carmine", "persianpink", "silversand", "firebrick", "pastelgreen", "viridiangreen", "indianred", "lightmediumorchid", "lightbeige", "dandelion", "mauvetaupe", "transparentdarkgreen", "ceil", "darkblue-gray", "amazon", "zaffre", "atomictangerine", "bistrebrown", "ceruleanfrost", "auburn", "gradientblack", "tuscanred", "anti-flashwhite", "gray", "darkturquoise", "champagne", "paleblue", "checkeredwhite", "coquelicot", "diamond", "darkbyzantium", "slateblue", "topaz", "pastelpink", "alabamacrimson", "xanadu", "midori", "falured", "arcticblue", "folly", "columbiablue", "tangelo", "debianred", "jade", "yaleblue", "skobeloff", "grey", "lion", "darkcandyapplered", "thistle", "violet", "silverlakeblue", "outerspace", "hanblue", "spanishblue", "persiangreen", "budgreen", "honeydew", "cornflowerblue", "classicrose", "strawberry", "darkblue", "pinkindigo", "carnationpink", "venetianred", "icterine", "babyblue", "raspberryglace", "mediumred-violet", "prune", "oucrimsonred", "richlilac", "darkcoffee", "barbiepink", "acidblue", "darkcyan", "tealblue", "mediumlavendermagenta", "sapphire", "tangopink", "brown-cream", "feldspar", "ashblack", "white", "darkred", "grannysmithapple", "zinnwalditebrown", "umber", "brilliantrose", "bronze", "pastelmagenta", "antiquefuchsia", "spanishcrimson", "darkterracotta", "wenge", "sunsetorange", "bottlegreen", "candyapplered", "aqua", "englishgreen", "persianorange", "parisgreen", "keppel", "multicolor", "queenpink", "berry", "cybergrape", "darkpastelpurple", "darkpink", "lightsteelblue", "spanishgray", "frenchraspberry", "armygreen", "imperialblue", "duststorm", "darkcerulean", "leopardprint", "avocado", "celeste", "olivedrab", "peru", "rosevale", "otterbrown", "superpink", "lightfuchsiapink", "stormcloud", "arsenic", "cosmiclatte", "royalfuchsia", "turquoisegreen", "brightturquoise", "rosebonbon", "deepruby", "fulvous", "vermilion", "purewhite", "indiagreen", "cadmiumorange", "spanishbistre", "darkpowderblue", "vividorchid", "uclagold", "aquamarine", "neonpink", "moonstoneblue", "caputmortuum", "amaranthpink", "robineggblue", "bananamania", "indianyellow", "mediumchampagne", "royalpurple", "deer", "aero", "onyx", "rosered", "lawngreen", "thulianpink", "turquoise-purple", "cottoncandy", "phlox", "liver", "upsdellred", "taupegray", "congopink", "ruby", "offwhite", "mediumpersianblue", "oldlavender", "red-orange", "hotmagenta", "scarlet", "etonblue", "pastelpurple", "vanilla", "blue-green", "cherryblossompink", "sunray", "fallow", "neonblue", "celadongreen", "taupe", "brightlavender", "newspaper", "officegreen", "fluorescentgreen", "deeplemon", "russiangreen", "lighttaupe", "cheddaryellow", "cheetahprint", "palecopper", "wildstrawberry", "shampoo", "darkgoldenrod", "tealdeer", "lust", "maygreen", "sandybrown", "mantis", "beaver", "windsortan", "frenchrose", "lightorchid", "heartgold", "ceruleanblue", "lightcornflowerblue", "deepfuchsia", "englishred", "darkorange", "yellow", "eggshell", "lava", "darkbrown", "naplesyellow", "toolbox", "rosybrown", "matteblack", "resolutionblue", "brightpink", "egyptianblue", "deepspacesparkle", "palegoldenrod", "whitegold", "cocoabrown", "carminered", "castletongreen", "mardigras", "vividburgundy", "bitterlemon", "antiquebrass", "springgreen", "lightwinered", "orchidpink", "tealgreen", "electriccyan", "lemon", "lemonmeringue", "purple-cream", "sonicsilver", "phthaloblue", "paleviolet-red", "eucalyptus", "tuscany", "battleshipgrey", "lavenderblush", "frenchwine", "waterspout", "cadetgrey", "electriclavender", "champagne-orange", "royalyellow", "palelavender", "napiergreen", "royalazure", "sinopia", "rosequartz", "seagreen", "turquoiseblack", "snowwhite", "mediumpurple", "harlequin", "indigo", "camel", "emerald", "checkeredred", "sepia", "darkolivegreen", "saffron", "lavenderrose", "tuscanbrown", "celerygreen", "harvardcrimson", "satinsheengold", "fuchsiapink", "oriolesorange", "floralprint", "bondiblue", "aureolin", "cherry", "b'dazzledblue", "mediumorchid", "bittersweetshimmer", "ultrapink", "silver", "grullo", "chartreuse", "mediumslateblue", "deepsaffron", "modebeige", "lightcrimson", "neonyellow", "denim", "peach-orange", "alaskablue", "turquoise", "persianindigo", "turkishrose", "lavendergray", "silvergrey", "mauvelous", "ultramarine", "sapphireblue", "manatee", "livid", "mediumvermilion", "oldrose", "uared", "shadow", "boysenberry", "deeplilac", "pearlaqua", "midnightgreen", "desire", "trueblue", "frenchpuce", "darkgreen", "cgred", "electriccrimson", "viridian", "nude", "darkviolet", "saddlebrown", "tigerprint", "darkpurple", "tyrianpurple", "shimmeringblush", "crystalclear", "spanishskyblue", "buff", "goldenbrown", "blackleatherjacket", "eggplant", "burntsienna", "babypowder", "tearose", "lakegreen", "mauve", "metallicsunburst", "guppiegreen", "palerobineggblue", "earthyellow", "majorelleblue", "metallicgold", "hanpurple", "irresistible", "rosemadder", "deepjunglegreen", "frenchmauve", "purple", "cerisepink", "darkmossgreen", "smitten", "warmblack", "salmonpink", "popstar", "pinklace", "piggypink", "bisque", "kellygreen", "palegreen", "babyblueeyes", "khaki", "bole", "electricpurple", "bluewash", "corn", "razzledazzlerose", "byzantium", "wisteria", "fluorescencegreen", "jazzberryjam", "vividskyblue", "mexicanpink", "ferngreen", "warmyellow", "bazaar", "ochre", "metallicseaweed", "iris", "cedarchest", "coralred", "blueyonder", "ube", "coral", "neoncarrot", "electricyellow", "cinnabar", "orangelight", "palegold", "calpolygreen", "beige", "ashgrey", "seashell", "azure", "oceanblue", "oldlace", "amber", "cadet", "citron", "charmpink", "rustyred", "unitednationsblue", "drab", "bitterlime", "lightrose", "antiqueruby", "jasper", "lightcoral", "deepchestnut", "rosegold", "nyanza", "maize", "chocolatebrown", "fluorescentblue", "paleaqua", "coolblack", "mediumspringgreen", "usafablue", "fuchsia", "blanchedalmond", "dartmouthgreen", "bigdipo'ruby", "deepcarminepink", "citrine", "goldenpoppy", "electricblue", "irishgreen", "spanishcarmine", "ivory", "oxfordblue", "brunswickgreen", "palecornflowerblue", "pinklight", "spanishviridian", "starcommandblue", "lightthulianpink", "forestgreen", "darkpastelred", "palesilver", "aeroblue", "lightpastelpurple", "selectiveyellow", "ruber", "woodbrown", "apricot", "brightblue", "daffodil", "junglegreen", "flirt", "brilliantlavender", "bubblegumpink", "pastelgray", "oldgold", "cerulean", "antiquegold", "darkslategray", "rust", "turquoiseblue", "prussianblue", "russet", "msugreen", "charcoal", "pinegreen", "warmwhite", "bronzeyellow", "maroon", "deepmauve", "lavenderpink", "blizzardblue", "africanpurple", "deeppeach", "meatbrown", "charlestongreen", "isabelline", "blast-offbronze", "newyorkpink", "transparentpink", "cream", "abyss", "redblack", "fuzzywuzzy", "darkmidnightblue", "inchworm", "peach", "ballblue", "dimgray", "redwood", "bubblegum", "blackgray", "junebud", "mediumaquamarine", "chineseviolet", "palatinateblue", "beaublue", "orchid", "watermelonred", "peakgreen", "urobilin", "waterwavecamouflage", "quartz", "mustard", "darkorchid", "teal", "chinesered", "ghostwhite", "mellowyellow", "deepcarrotorange", "lightcoffee", "vanillaice", "grassgreen", "celadonblue", "bluelightwash", "brass", "fluorescentyellow", "desert", "myrtlegreen", "pakistangreen", "darklava", "vividtangerine", "verdigris", "spirodiscoball", "brown-nose", "navyblue", "lightmossgreen", "arylideyellow", "twilightlavender", "davy'sgrey", "mediumcandyapplered", "blackwhite"];
		var otherColorDataA = ["Fawn", "French lilac", "Dark raspberry", "Pink lavender", "Copper penny", "Bright navy blue", "Chamoisee", "Pale plum", "Forest camouflage", "Olive", "Dark sea green", "French beige", "Metallic Bronze", "Rose taupe", "Dark chestnut", "Orange peel", "Desert sand", "Light khaki", "Pale brown", "Mint cream", "Grape", "Gold", "Fashion fuchsia", "Mountain Meadow", "Dutch white", "Fushcia", "UCLA Blue", "Lapis lazuli", "Telemagenta", "China pink", "Neon red", "Illuminating Emerald", "Rhythm", "Dark spring green", "Crimson", "Islamic green", "Regalia", "Pastel red", "Greyish white", "Dark lavender", "Brown", "Green-yellow", "Imperial red", "Barn red", "Matte White", "Outrageous Orange", "Carrot orange", "Veronica", "Fluorescent orange", "Dark taupe", "Portland Orange", "Tangerine yellow", "Cyan", "Bubbles", "Cobalt", "Peridot", "Rich electric blue", "Sky blue", "French sky blue", "Little boy blue", "Brink pink", "Asparagus", "Dark gray", "Medium spring bud", "Moccasin", "Platinum", "Light pink", "Sand", "Caribbean green", "Red", "North Texas Green", "Purple taupe", "Bulgarian rose", "Olivine", "Claret", "Purple fuchsia", "Japanese indigo", "Steel Blue-Purple", "China rose", "Light goldenrod yellow", "Almond", "Deep coffee", "Lavender", "Wild blue yonder", "Neon green", "Wheat", "Stil de grain yellow", "Blue", "Yellow Orange", "New Car", "Magnolia", "Zebra print", "Sangria", "Burgundy", "Purplish red", "Rosso corsa", "Chrome yellow", "Carolina blue", "Pink Sherbet", "Rust brown", "French lime", "KU Crimson", "Deep cerise", "Deep sky blue", "Sunglow", "Puce red", "Dark vanilla", "Nadeshiko pink", "Bright cerulean", "Yankees blue", "Kenyan copper", "Jasmine", "Mountbatten pink", "Lemon lime", "Red-brown", "Russian violet", "UFO Green", "Old mauve", "Rose ebony", "Cadmium green", "Electric violet", "Flavescent", "Purple mountain majesty", "Floral white", "Yellow camouflage", "Vivid violet", "Flamingo pink", "Metallic Silver", "Azure mist", "Gray-blue", "Cinnamon", "Antique bronze", "Light red ochre", "Pale spring bud", "Sacramento State green", "Deep moss green", "Mughal green", "Lemon curry", "Golden", "Ruby red", "Spartan Crimson", "Green plaid", "Straw", "French bistre", "Light sea green", "Purple Heart", "Off-white", "Sienna", "Vivid cerise", "Skyblue Orange", "Purple pizzazz", "Raw umber", "Cameo pink", "Rosewood", "Gamboge", "Copper red", "Ebony", "Lime", "White Dye Dip", "Princeton orange", "Shocking pink", "Blue sapphire", "Wine red", "Royal blue", "Lake blue", "Lavender magenta", "Tuscan", "Pearl", "Capri", "French blue", "Fresh Air", "Pale carmine", "Blueberry", "Cerise", "Chestnut", "Blue-gray", "Bright green", "Light yellow", "Mikado yellow", "Pastel violet", "Tangerine", "Flax", "Lemon chiffon", "Chocolate", "Melon", "Cordovan", "Aqua blue", "Dark grey", "Psychedelic purple", "Catawba", "Medium carmine", "Fandango pink", "Dark tangerine", "Silver green", "Antique white", "Tiger's eye", "Pale taupe", "Baker-Miller pink", "Patriarch", "Dark slate blue", "Light sky blue", "Sandstorm", "Rufous", "Lumber", "Languid lavender", "Deep pink", "Fandango", "Pumpkin", "Plum", "Pale red-violet", "Bittersweet", "Timberwolf", "Pale turquoise", "Light brown", "Dodger blue", "Honolulu blue", "Liver chestnut", "Navy purple", "Magenta", "Tan", "Medium ruby", "Pink", "Purple light", "Vivid auburn", "Black fuchsia", "Safety yellow", "Pistachio", "Persimmon", "Jelly Bean", "Khaki Green", "Orange-red", "Mellow apricot", "Giants orange", "Mango Tango", "Cardinal", "Rose", "Cheetah", "Pale cerulean", "Midnight blue", "Rich carmine", "Coral pink", "Purple navy", "Yellow rose", "Dark jungle green", "Transparent White", "Bright ube", "Mordant red 19", "Non-photo blue", "Dark liver", "Fuchsia-Black", "Light slate gray", "Opera mauve", "Floral", "Magenta haze", "Japanese violet", "Camel brown", "Rubine red", "Medium sea green", "Light gray", "Smoke", "Heliotrope", "Tractor red", "Cadet blue", "Blue Bell", "Ultramarine blue", "Maya blue", "Silver white", "Ruddy brown", "Tickle Me Pink", "Linen", "Light leopard", "Green", "Light tan", "Powder blue", "Bluebonnet", "Tiffany Blue", "Peach puff", "Cherry Pink", "Wine", "Baby pink", "Kombu green", "Medium violet-red", "Tuscan tan", "Dark coral", "Smoky black", "Nacarat", "Yellow-green", "Italian sky blue", "USC Cardinal", "Violet-red", "Sandy taupe", "Electric indigo", "Cambridge Blue", "Khaki Beige", "Lavender mist", "Dark imperial blue", "Shamrock green", "Indigo dye", "Razzmatazz", "Malachite", "Screamin' Green", "Silver pink", "Seal brown", "Medium blue", "Electric lime", "Orange", "Lincoln green", "La Salle Green", "Canary yellow", "Antique Black", "Smalt", "Coffee", "Lemon yellow", "AuroMetalSaurus", "Brandeis blue", "Pink pearl", "English violet", "Moss green", "UP Maroon", "Pastel orange", "Dollar bill", "Pink-orange", "Wine dregs", "Persian rose", "Wild orchid", "Fuchsia rose", "Jonquil", "Pastel yellow", "Cocoa", "Candy pink", "Goldenrod", "Catalina blue", "UA blue", "Razzmic Berry", "Tiger", "Sunset", "Bright maroon", "Duke blue", "Ecru", "Deep Taupe", "Radical Red", "Safety orange", "Wild Watermelon", "Dark magenta", "Apple green", "Utah Crimson", "Old moss green", "Bone", "Bright red", "Hansa yellow", "Tumbleweed", "Pastel blue", "Lilac", "Red devil", "Mahogany", "Orange plaid", "Generic viridian", "Medium turquoise", "Multicolored", "Medium taupe", "Zebra", "Queen blue", "Navy", "Cadmium yellow", "Trolley Grey", "Ocean Boat Blue", "Sandy", "Celestial blue", "Olive green", "Dark army green", "Dark plum", "Navajo white", "Deep Tuscan red", "Milk white", "Rich lavender", "Hunter green", "Light purple", "Dark khaki", "Palatinate purple", "Sea blue", "Stizza", "Peach-yellow", "Amaranth", "Amethyst", "Steel pink", "USC Gold", "Light carmine pink", "Cornsilk", "Independence", "Ruddy", "Slate gray", "Ferrari Red", "Donkey Brown", "Acid green", "Dirt", "Leopard", "Bistre", "Paolo Veronese green", "Crimson glory", "Steel blue", "Gray-asparagus", "Magic mint", "Rose pink", "Carnelian", "Alice blue", "Copper rose", "Schauss pink", "Gainsboro", "Water blue", "Rifle green", "Feldgrau", "Phthalo green", "Pale pink", "Ink blue", "Light grey", "Light cyan", "Alizarin crimson", "Dark salmon", "Rocket metallic", "Bleu de France", "Lavender purple", "Blush", "Medium jungle green", "Tea green", "Burnt orange", "Copper", "Puce", "Unmellow yellow", "Licorice", "Limerick", "Tufts Blue", "Cool grey", "Misty rose", "UP Forest green", "Kobi", "Amber gold", "Electric green", "Dark sienna", "Kobe", "Eminence", "Imperial", "Glitter", "Sky magenta", "Dark yellow", "Lavender indigo", "Hollywood cerise", "Jet", "Spring bud", "Burlywood", "Red-violet", "Terra cotta", "Carmine pink", "Rich black", "Electric ultramarine", "Camouflage green", "Laser Lemon", "Spanish orange", "Periwinkle", "Cinereous", "Purpureus", "Transparent red", "Dark sky blue", "Tomato", "Dark tan", "Cadmium red", "Laurel green", "Neon fuchsia", "Heather Gray", "Pale chestnut", "English lavender", "Denim blue", "Green Apple", "Green light", "Light salmon", "Rich maroon", "Flattery", "Dark electric blue", "Iceberg", "Hot pink", "Raspberry", "Lime green", "Dark pastel green", "Pear", "Peacock blue", "Celadon", "Dark scarlet", "Blue-violet", "Flame", "Fire engine red", "Gold Fusion", "Light salmon pink", "Golden yellow", "Coconut", "Persian blue", "Titanium yellow", "Android green", "Washed black", "Pansy purple", "Olive green black", "Ginger", "Payne's grey", "Light green", "Persian plum", "Blond", "CG Blue", "Roman silver", "Mint", "Pastel brown", "Byzantine", "White smoke", "Light blue", "Violet-blue", "Rich brilliant lavender", "Fluorescent rose", "Raspberry rose", "Tulip", "Deep magenta", "Vegas gold", "Snow", "Camouflage", "Tropical rain forest", "Medium electric blue", "Banana yellow", "Rajah", "Zomp", "Fluorescent pink", "Raspberry pink", "Unbleached silk", "Soap", "Transparent", "Yellow light", "Silver chalice", "Lemon glacier", "Old silver", "Salmon", "Antique silver", "Harvest gold", "Medium sky blue", "Air Force blue", "Smokey topaz", "Ruddy pink", "Alloy orange", "Field drab", "Pictorial carmine", "Clear", "Imperial purple", "Mulberry", "Black bean", "American rose", "School bus yellow", "Deep carmine", "Medium Tuscan red", "Brick red", "Lavender blue", "Sand dune", "Dark pastel blue", "Silver gray", "Cyber yellow", "Asphalt", "Burnt umber", "Sheen Green", "Neon orange", "Light apricot", "Pale magenta", "Black olive", "Glaucous", "Old burgundy", "Persian red", "Dogwood rose", "Liberty", "Coral green", "Pearly purple", "Mint green", "Sap green", "British racing green", "Cornell Red", "Deep champagne", "Pink Beige", "Rackley", "Blue-Cream", "Papaya whip", "Space cadet", "Black", "Heather grey", "Carmine", "Persian pink", "Silver sand", "Firebrick", "Pastel green", "Viridian green", "Indian red", "Light medium orchid", "Light beige", "Dandelion", "Mauve taupe", "Transparent dark green", "Ceil", "Dark blue-gray", "Amazon", "Zaffre", "Atomic tangerine", "Bistre brown", "Cerulean frost", "Auburn", "Gradient black", "Tuscan red", "Anti-flash white", "Gray", "Dark turquoise", "Champagne", "Pale blue", "Checkered White", "Coquelicot", "Diamond", "Dark byzantium", "Slate blue", "Topaz", "Pastel pink", "Alabama crimson", "Xanadu", "Midori", "Falu red", "Arctic blue", "Folly", "Columbia blue", "Tangelo", "Debian red", "Jade", "Yale Blue", "Skobeloff", "Grey", "Lion", "Dark candy apple red", "Thistle", "Violet", "Silver Lake blue", "Outer Space", "Han blue", "Spanish blue", "Persian green", "Bud green", "Honeydew", "Cornflower blue", "Classic rose", "Strawberry", "Dark blue", "Pink indigo", "Carnation pink", "Venetian red", "Icterine", "Baby blue", "Raspberry glace", "Medium red-violet", "Prune", "OU Crimson Red", "Rich lilac", "Dark coffee", "Barbie pink", "Acid blue", "Dark cyan", "Teal blue", "Medium lavender magenta", "Sapphire", "Tango pink", "Brown-Cream", "Feldspar", "Ash black", "White", "Dark red", "Granny Smith Apple", "Zinnwaldite brown", "Umber", "Brilliant rose", "Bronze", "Pastel magenta", "Antique fuchsia", "Spanish crimson", "Dark terra cotta", "Wenge", "Sunset orange", "Bottle green", "Candy apple red", "Aqua", "English green", "Persian orange", "Paris Green", "Keppel", "Multicolor", "Queen pink", "Berry", "Cyber grape", "Dark pastel purple", "Dark pink", "Light steel blue", "Spanish gray", "French raspberry", "Army green", "Imperial blue", "Dust storm", "Dark cerulean", "Leopard print", "Avocado", "Celeste", "Olive Drab", "Peru", "Rose vale", "Otter brown", "Super pink", "Light fuchsia pink", "Stormcloud", "Arsenic", "Cosmic latte", "Royal fuchsia", "Turquoise green", "Bright turquoise", "Rose bonbon", "Deep ruby", "Fulvous", "Vermilion", "Pure white", "India green", "Cadmium orange", "Spanish bistre", "Dark powder blue", "Vivid orchid", "UCLA Gold", "Aquamarine", "Neon pink", "Moonstone blue", "Caput mortuum", "Amaranth pink", "Robin egg blue", "Banana Mania", "Indian yellow", "Medium champagne", "Royal purple", "Deer", "Aero", "Onyx", "Rose red", "Lawn green", "Thulian pink", "Turquoise-Purple", "Cotton candy", "Phlox", "Liver", "Upsdell red", "Taupe gray", "Congo pink", "Ruby", "Off white", "Medium Persian blue", "Old lavender", "Red-orange", "Hot magenta", "Scarlet", "Eton blue", "Pastel purple", "Vanilla", "Blue-green", "Cherry blossom pink", "Sunray", "Fallow", "Neon blue", "Celadon green", "Taupe", "Bright lavender", "Newspaper", "Office green", "Fluorescent green", "Deep lemon", "Russian green", "Light taupe", "Cheddar Yellow", "Cheetah print", "Pale copper", "Wild Strawberry", "Shampoo", "Dark goldenrod", "Teal deer", "Lust", "May green", "Sandy brown", "Mantis", "Beaver", "Windsor tan", "French rose", "Light orchid", "Heart Gold", "Cerulean blue", "Light cornflower blue", "Deep fuchsia", "English red", "Dark orange", "Yellow", "Eggshell", "Lava", "Dark brown", "Naples yellow", "Toolbox", "Rosy brown", "Matte Black", "Resolution blue", "Bright pink", "Egyptian blue", "Deep Space Sparkle", "Pale goldenrod", "White gold", "Cocoa brown", "Carmine red", "Castleton green", "Mardi Gras", "Vivid burgundy", "Bitter lemon", "Antique brass", "Spring green", "Light wine red", "Orchid pink", "Teal green", "Electric cyan", "Lemon", "Lemon meringue", "Purple-Cream", "Sonic silver", "Phthalo blue", "Pale violet-red", "Eucalyptus", "Tuscany", "Battleship grey", "Lavender blush", "French wine", "Waterspout", "Cadet grey", "Electric lavender", "Champagne-Orange", "Royal yellow", "Pale lavender", "Napier green", "Royal azure", "Sinopia", "Rose quartz", "Sea green", "Turquoise black", "Snow White", "Medium purple", "Harlequin", "Indigo", "Camel", "Emerald", "Checkered Red", "Sepia", "Dark olive green", "Saffron", "Lavender rose", "Tuscan brown", "Celery green", "Harvard crimson", "Satin sheen gold", "Fuchsia pink", "Orioles orange", "Floral print", "Bondi blue", "Aureolin", "Cherry", "B'dazzled blue", "Medium orchid", "Bittersweet shimmer", "Ultra pink", "Silver", "Grullo", "Chartreuse", "Medium slate blue", "Deep saffron", "Mode beige", "Light crimson", "Neon yellow", "Denim", "Peach-orange", "Alaska blue", "Turquoise", "Persian indigo", "Turkish rose", "Lavender gray", "Silver grey", "Mauvelous", "Ultramarine", "Sapphire blue", "Manatee", "Livid", "Medium vermilion", "Old rose", "UA red", "Shadow", "Boysenberry", "Deep lilac", "Pearl Aqua", "Midnight green", "Desire", "True Blue", "French puce", "Dark green", "CG Red", "Electric crimson", "Viridian", "Nude", "Dark violet", "Saddle brown", "Tiger print", "Dark purple", "Tyrian purple", "Shimmering Blush", "Crystal Clear", "Spanish sky blue", "Buff", "Golden brown", "Black leather jacket", "Eggplant", "Burnt sienna", "Baby powder", "Tea rose", "Lake green", "Mauve", "Metallic Sunburst", "Guppie green", "Pale robin egg blue", "Earth yellow", "Majorelle Blue", "Metallic Gold", "Han purple", "Irresistible", "Rose madder", "Deep jungle green", "French mauve", "Purple", "Cerise pink", "Dark moss green", "Smitten", "Warm black", "Salmon pink", "Popstar", "Pink lace", "Piggy pink", "Bisque", "Kelly green", "Pale green", "Baby blue eyes", "Khaki", "Bole", "Electric purple", "Blue Wash", "Corn", "Razzle dazzle rose", "Byzantium", "Wisteria", "Fluorescence green", "Jazzberry jam", "Vivid sky blue", "Mexican pink", "Fern green", "Warm Yellow", "Bazaar", "Ochre", "Metallic Seaweed", "Iris", "Cedar Chest", "Coral red", "Blue yonder", "Ube", "Coral", "Neon Carrot", "Electric yellow", "Cinnabar", "Orange light", "Pale gold", "Cal Poly green", "Beige", "Ash grey", "Seashell", "Azure", "Ocean blue", "Old lace", "Amber", "Cadet", "Citron", "Charm pink", "Rusty red", "United Nations blue", "Drab", "Bitter lime", "Light rose", "Antique ruby", "Jasper", "Light coral", "Deep chestnut", "Rose gold", "Nyanza", "Maize", "Chocolate brown", "Fluorescent blue", "Pale aqua", "Cool black", "Medium spring green", "USAFA blue", "Fuchsia", "Blanched almond", "Dartmouth green", "Big dip o'ruby", "Deep carmine pink", "Citrine", "Golden poppy", "Electric blue", "Irish green", "Spanish carmine", "Ivory", "Oxford Blue", "Brunswick green", "Pale cornflower blue", "Pink light", "Spanish viridian", "Star command blue", "Light Thulian pink", "Forest green", "Dark pastel red", "Pale silver", "Aero blue", "Light pastel purple", "Selective yellow", "Ruber", "Wood brown", "Apricot", "Bright blue", "Daffodil", "Jungle green", "Flirt", "Brilliant lavender", "Bubblegum Pink", "Pastel gray", "Old gold", "Cerulean", "Antique Gold", "Dark slate gray", "Rust", "Turquoise blue", "Prussian blue", "Russet", "MSU Green", "Charcoal", "Pine green", "Warm white", "Bronze Yellow", "Maroon", "Deep mauve", "Lavender pink", "Blizzard Blue", "African purple", "Deep peach", "Meat brown", "Charleston green", "Isabelline", "Blast-off bronze", "New York pink", "Transparent pink", "Cream", "Abyss", "Red black", "Fuzzy Wuzzy", "Dark midnight blue", "Inchworm", "Peach", "Ball blue", "Dim gray", "Redwood", "Bubble gum", "Black gray", "June bud", "Medium aquamarine", "Chinese violet", "Palatinate blue", "Beau blue", "Orchid", "Watermelon red", "Peak green", "Urobilin", "Water wave camouflage", "Quartz", "Mustard", "Dark orchid", "Teal", "Chinese red", "Ghost white", "Mellow yellow", "Deep carrot orange", "Light coffee", "Vanilla ice", "Grass green", "Celadon blue", "Blue Light Wash", "Brass", "Fluorescent yellow", "Desert", "Myrtle green", "Pakistan green", "Dark lava", "Vivid tangerine", "Verdigris", "Spiro Disco Ball", "Brown-nose", "Navy blue", "Light moss green", "Arylide yellow", "Twilight lavender", "Davy's grey", "Medium candy apple red", "Black white"];
		var goodsSizeData = ['X', 'L', 'XL', 'XXL'];
		colorRun();

		$('.colorAdd').click(function() {
			var color = $('#otherColor').val();
			if (Ensogo.isContainChinese(color)) {
				Ensogo.tips({
					type: "error",
					msg: "请输入英文"
				});
				return;
			}

			//判断input.otherColor中新加的颜色在不在goodsColorData;若在，则清空input.otherColor中内容，并且在goodsColor中寻找匹配的颜色，将其选中
			for (var n = 0; n < goodsColorData.length; n++) {
				if (color.toLowerCase().replace(/ /g, "") == goodsColorData[n]['colorId'].toLowerCase()) {
					$('#otherColor').val("");
					// console.log(color.toLowerCase().replace(/ /g, ""));
					// console.log(goodsColorData[n]['colorId'].toLowerCase());
					$('#goodsColor').find('input[type="checkbox"]').each(function() {
						if ($(this).val().toLowerCase() == escape(color.toLowerCase().replace(/ /g, ""))) {
							$(this).attr('checked', 'true');
						}
					});
					return;
				};
			};
			if (color != '') { //若input.otherColor中的内容不为空，则将input.otherColor中的颜色和otherColorDataB中的颜色进行比较判断，若相等，则和newColor数组比较，若newColor中有则返回；将input.otherColor和otherColorDataA比较，若相等则在goodsColor中插入该颜色的div 并且将input.otherColor添加到newColor数组中,清空input.otherColor中的内容，运行selColor方法;

				for (var k = 0; k < otherColorDataB.length; k++) {
					if (otherColorDataB[k].toLowerCase() == color.toLowerCase().replace(/ /g, "")) {
						var iptColorId = otherColorDataB[k];
						for (var i = 0; i < newColor.length; i++) {
							if (color.toLowerCase().replace(/ /g, "") == newColor[i].toLowerCase()) {

								return;
							};
						};
						for (var j = 0; j < otherColorDataA.length; j++) {
							if (color.toLowerCase() == otherColorDataA[j].toLowerCase()) {
								//新加颜色写到新数组里便于下次添加判断
								newColor.push(iptColorId);
								//写入样式
								$('#goodsColor').append('<div class="col-xs-2 mTop10 text-left" style="padding:0;"><input type="checkbox" checked="true" name="checkbox" value="' + iptColorId + '"><span>' + otherColorDataA[j] + '</span></div>');
								Ensogo.ajustHeight('color');
								//运行添加颜色方法
								selColor(iptColorId);
								//清空输入框
								$('#otherColor').val("");
							}
						};
					} else { //若input.otherColor中的内容为空，则将input.otherColor和newColor数组进行比较，若newColor中有则返回,否则将input.otherColor插入newColor数组中，并且在goodsColor中插入该颜色的div,清空input.otherColor内容,运行selColor方法;
						var iptColorId = escape(color.replace(/ /g, "")).toLowerCase();
						for (var i = 0; i < newColor.length; i++) {
							if (iptColorId == newColor[i].toLowerCase()) {
								return;
							};
						};
						newColor.push(iptColorId);
						//写入样式
						$('#goodsColor').append('<div class="col-xs-2 mTop10 text-left" style="padding:0;"><input type="checkbox" checked="true" name="checkbox" value="' + iptColorId + '"><span>' + color.replace(/(\w)/, function(v) { return v.toUpperCase() }) + ' </span></div>');
						Ensogo.ajustHeight('color');
						//运行添加颜色方法
						selColor(iptColorId);
						//清空输入框
						$('#otherColor').val("");
					};
				};
			};
		});

		//颜色check点击方法
		$(document).on('click', '#goodsColor input[type=checkbox]', function(event) {
			//触发当前事件的源对象 target是firefox下的属性，srcElement是IE下的属性

			var target = event.target || event.srcElement,

				colorId = $(target).val();
			!!target.checked ? selColor(colorId) : unSelColor(colorId); //！！相当于boolean()

		});
		//尺寸check点击方法
		$(document).on('click', '#goodsSize input[type=checkbox]', function(event) {
			var target = event.target || event.srcElement,
				size = $(target).val();
			!!target.checked ? selSize(size) : unSelSize(size);
		});
		$('.sizeAdd').click(function() {
			var size = $('#otherSize').val();
			if (Ensogo.isContainChinese(size)) {
				Ensogo.tips({
					type: "error",
					msg: "请输入英文"
				});
				return;
			}
			for (var n = 0; n < goodsSizeData.length; n++) {
				if (size.toLowerCase().replace(/ /g, "") == goodsSizeData[n].toLowerCase()) {
					$('#otherSize').val("");
					$('#goodsSize').find('input[type="checkbox"]').each(function() {
						if ($(this).val().toLowerCase() == escape(size.toLowerCase().replace(/ /g, ""))) {
							console.log($(this).val());
							$(this).attr('checked', 'true');
						}
					});
					return;
				};
			};
			if (size != '') {
				var iptSizeId = escape(size.replace(/ /g, ""));
				for (var i = 0; i < newSize.length; i++) {
					if (iptSizeId.toLowerCase() == newSize[i].toLowerCase()) {
						return;
					};
				};
				newSize.push(iptSizeId);
				//写入样式
				$('#goodsSize').append('<div class="col-xs-1 mTop10 minW110 text-left"><input type="checkbox" name="checkbox" checked="true" value="' + size + '"><span>' + size + ' </span></div>');
				Ensogo.ajustHeight('size');
				//运行添加颜色方法
				selSize(iptSizeId);
				//清空输入框
				$('#otherColor').val("");
			}
		});

		//产品描述富文本编辑器
		// KindEditor.ready(function(K){
		// 	window.editor = K.create('#ensogo_product_description',{minWidth:'570px',height:'400px',});
		// });

		//右侧菜单滑动事件
		$(window).scroll(function(event) {
			var winPos = $(window).scrollTop();
			var $ensogo_product_baseinfo = $('.ensogo_product_baseinfo').offset().top;
			var $ensogo_product_image = $('.ensogo_product_image').offset().top - 3;
			var $ensogo_product_variance = $('.ensogo_product_variance').offset().top - 3;
			// console.log(winPos);
			// console.log($ensogo_product_baseinfo);
			// console.log($ensogo_product_image);
			// console.log($ensogo_product_variance);
			// console.log(gotowhere);
			if (Ensogo.isClick == false) {
				if (winPos < $ensogo_product_image) {
					Ensogo.showscrollcss('ensogo_product_baseinfo');
				} else if (winPos >= $ensogo_product_image && winPos < $ensogo_product_variance) {
					Ensogo.showscrollcss('ensogo_product_image');
				} else if (winPos >= $ensogo_product_variance) {
					Ensogo.showscrollcss('ensogo_product_variance');
				}
			}
		});

		//发布站点选中
		$('input[name="all_sites"]').click(function(){
			// console.log(Ensogo.selSites);						
			$(this).is(':checked') ? 
				function(){
					$('input[name="sites"]').prop('checked','true');
					$('input[name= "sites"]:checked').each(function(){
						($.inArray($(this).data('key'),Ensogo.selSites) != -1) || Ensogo.selSites.push($(this).data('key'));
						Ensogo.selSites.sort(Ensogo.site_sort) ;
						$(this).prop('checked','true');
					});
					$('tr[name="less"]').remove();
					$('.site_info').remove();
					var price = $('#ensogo_product_price').val();
					var msrp = $('#ensogo_product_sale_price').val();
					var shipping = $('#ensogo_product_shipping').val();
					$('#goodsList').html('');
					if($('input[name="ensogo_id"]').data('type') == 'offline'){
						for(var i=0,len=Ensogo.selColorArr.length;i<len;i++){
							selColor(Ensogo.selColorArr[i]);
						}
						$('td.rowspan').attr('rowspan',Ensogo.selSites.length);
					}else{
						Ensogo.fillSitesData();
					}
					$('td.rowspan').attr('rowspan',Ensogo.selSites.length);
				}() :
				function(){
					$('input[name="all_sites"]').prop('checked','true');
					Ensogo.tips({type:'error',msg:'发布站数量不能为零!',existTime:3000});
					return false;
					// $('input[name="sites"]:checked').each(function(){
					// 	($.inArray($(this).data('key'),Ensogo.selSites) == -1) || Ensogo.selSites.splice($.inArray($(this).data('key'),Ensogo.selSites),1);
					// 	$(this).removeAttr('checked');
					// });
				}();
		});


		

		$('input[name="sites"]').click(function(){
			var key = $(this).data('key');
			if($('input[name="sites"]:checked').length == 0){
				$(this).prop('checked','true');
				Ensogo.tips({type:'error',msg:'发布站点数量不能为零!',existTime:3000});
				return false;
			}
			$(this).is(':checked') ?
				function(){
					($.inArray(key,Ensogo.selSites) != -1) || Ensogo.selSites.push(key);
					Ensogo.selSites.sort(Ensogo.site_sort);
					$(this).prop('checked','true');
				}() :
				function(){
					($.inArray(key,Ensogo.selSites) == -1) || Ensogo.selSites.splice($.inArray(key,Ensogo.selSites),1);
					$(this).removeAttr('checked');
				}();
			$('input[name="sites"]:checked').length == 7 ? $('input[name="all_sites"]').prop('checked','true') : $('input[name="all_sites"]').removeAttr('checked');
			$('tr[name="less"]').remove();
			$('.site_info').remove();
			var price = $('#ensogo_product_price').val();
			var msrp = $('#ensogo_product_sale_price').val();
			var shipping = $('#ensogo_product_shipping').val();
			$('#goodsList').html('');
			if($('input[name="ensogo_id"]').data('type') == 'offline'){
				if($('input[name="sale_choose"]:checked').val() == 'single'){
					$('input[name="sale_choose"][value="single"]').click();
				}else{
					for(var i=0,len=Ensogo.selColorArr.length;i<len;i++){
						selColor(Ensogo.selColorArr[i]);
					}
				}
			}else{
				Ensogo.fillSitesData();
			}
			$('td.rowspan').attr('rowspan',Ensogo.selSites.length);
		});

		//售卖形式选择
		$('input[name="sale_choose"]').on('click',function(){
			$(this).val() == 'single'?function(){
									$('.multi_list').hide();
									$('.header_title').remove();
									$('#goodsList').html('');
									$('#goodsList tr').length  != 0  || function(){
										var rowspan = Ensogo.selSites.length;
										var price = $('#ensogo_product_price').val();
										var msrp = $('#ensogo_product_sale_price').val();
										var shipping = $('#ensogo_product_shipping').val();
										var inventory = $('#ensogo_product_count').val();
										var parent_sku = $('#ensogo_product_parentSku').val();
										var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
										var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
										var sku = createSku(parent_sku);
										var str = '<tr name="main" style="border-top:1px solid #CCC;">';
										// str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"></td>';
										// str +='<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"></td>';
										str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="'+ sku +'"></td>';
										str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
										str += '<td class="col-xs-1 sites_ajust" style="text-align:center" rowspan="'+ rowspan +'" class="rowspan">';
										str += '<input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>';
										for(var key in Ensogo.selSites){
											if(Ensogo.selSites.hasOwnProperty(key)){
												str += '<td class="site_info" style="text-align:center;min-width:70px;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]] +'</td>';
												str += '<td class="site_info" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
												str += '<td class="site_info" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
												str += '<td class="site_info" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
												(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less">');
											}		
										}		
										$('#goodsList').append(str);	
										}();
								 	}():function(){
										$('.multi_list').show();
										$('.header_title').remove();
										$('.bgColor1').append('<th class="header_title" style="text-align:center;line-height:30px;">操作</th>');
										$('.bgColor1').prepend('<th class="col-xs-1 header_title" style="text-align:center;line-height:30px;">颜色</th><th class="col-xs-1 header_title" style="text-align:center;line-height:30px;">尺寸</th>');
										$('#goodsList').html('');
										for(var i=0,len=Ensogo.selColorArr.length;i<len;i++){
											selColor(Ensogo.selColorArr[i]);
										}
								  	}();
		});

		//产品标签检测
		//触发时注册以上方法
		$('#ensogo_product_tags').blur(function() {
			var tagsNum = $('#goods_tags span.tag').length;
			var currentTxt = $(this).val();
			var tagArr = currentTxt.split(',');
			var agginMemory = [];
			for (var j = 0; j < tagArr.length; j++) {
				var isAgin = 0;
				for (var i = 0; i < Ensogo.memoryData.length; i++) {
					if (Ensogo.memoryData[i].toLowerCase() == tagArr[j].toLowerCase()) {
						isAgin = 1;
					}
				}
				if (isAgin == 0 && tagsNum < 10) {
					createTag(this, tagArr[j]); //运行写入span方法
				} else {
					agginMemory.push(tagArr[j]);
				}
				// removeTag();   //注册删除事件
			};
			if (tagsNum >= 10) {
				Ensogo.tips({
					type: "error",
					msg: "产品标签最多可添加 10个",
					existTime: 3000
				});
				return false;
			}

			if (agginMemory.length > 0) {
				var str = '';
				for (var i = 0; i < agginMemory.length; i++) {
					if (str != '') {
						var newStr = ',' + agginMemory[i];
						str += newStr;
					} else {
						str += agginMemory[i];
					}
				}
				Ensogo.tips({
					type: "error",
					msg: str + "为重复的标签",
					existTime: 3000
				});
			}
		});

		$('#ensogo_product_tags').keyup(function(event) {

			event = event || window.event;
			var currentTxt = $(this).val();
			var tagsNum = $('#goods_tags span.tag').length;
			//console.log(event.which);
			if (event.which == "13" || event.which == "188") { //判断是不是回车键或","号键
				event.keyCode = '13';
				//把键值改为0防止其他事件发生
				var tagArr = currentTxt.split(',');
				var agginMemory = [];
				// console.log(tagArr);
				for (var j = 0; j < tagArr.length; j++) {
					var isAgin = 0;
					for (var i = 0; i < Ensogo.memoryData.length; i++) {
						if (Ensogo.memoryData[i].toLowerCase() == tagArr[j].toLowerCase()) {
							isAgin = 1;
						}
					}
					if (isAgin == 0 && tagsNum < 10) {
						createTag(this, tagArr[j]); //运行写入span方法
					} else {
						agginMemory.push(tagArr[j]);
					}
				}
				if (tagsNum >= 10) {
					Ensogo.tips({
						type: "error",
						msg: "产品标签最多可添加 10个",
						existTime: 3000
					});
					return false;
				}
				if (agginMemory.length > 0) {
					var str = '';
					for (var i = 0; i < agginMemory.length; i++) {
						if (str != '') {
							var newStr = ',' + agginMemory[i];
							str += newStr;
						} else {
							str += agginMemory[i];
						}
					}
					Ensogo.tips({
						type: "error",
						msg: str + "为重复的标签",
						existTime: 3000
					});
				}
			};
		});

		 $( "#ensogo_product_tags" ).autocomplete({
	        source: function(request,response){
	            $.ajax({
	               type: "post",
	               url: "/listing/ensogo-offline/get-tags",
	               data: { q : request.term},
	            }).done(function(data){
	                function cmp(a, b) {
	                            if (a > b) { return 1; }
	                            if (a < b) { return -1; }
	                            return 0;
	                        }
	                function by(keyword) {
	                    keyword = keyword.toLowerCase();
	                    return function(a, b) {
	                        a = a.toLowerCase();
	                        b = b.toLowerCase();
	                        var i = a.indexOf(keyword);
	                        var j = b.indexOf(keyword);

	                        if (i === 0) {
	                            if (j === 0) {
	                                return cmp(a, b);
	                            }
	                            else {
	                                return -1;
	                            }
	                        }
	                        else {
	                            if (j === 0) {
	                                return 1;
	                            }
	                            return cmp(a, b);
	                        }
	                    };
	                };
	                // console.log(data);
	                data.sort(by(request.term));
	                //console.log(eval(data));
	                response(data);
	                
	            });
	        }

	    });

		$('.ensogo_product_category_btn').click(function() {
			var site_id = $('select[name="site_id"]').val();
			if (site_id == 0 || site_id == '') {
				Ensogo.tips({
					type: 'error',
					msg: '请选择店铺',
					existTime: 3000
				});
				return false;
			}
			var parentCategoryId = -1;
			// console.log('www')
			$.ajax({
				type: 'post',
				data: 'parentCategoryId=' + parentCategoryId + '&site_id=' + site_id,
				url: 'get-parent-category-id',
				success: function(data) {
					// console.log(data);
					var str = '<div class="modal fade category-modal-lg" id="Category_modal" tabindex="-1" role="dialog" aria-labelledby="CategorySelectList">';
					str += '<div class="modal-dialog">';
					str += '<div class="modal-content" style="border:0;width:825px;">';
					str += '<div class="modal-header" style="background-color:#364655;">';
					str += '<h4 class="modal-title" id="CategorySelectListTitle" style="color:white;">选择分类</h4>';
					str += '<div class="modal-body" style="background-color:white;width:825px;">';
					str += '<div class="container-fluid"><div class="row"><ul class="nav nav-pills nav-stacked col-md-4 col-xs-4 category category_1">';
					for (var i = 0; i < data.length; i++) {
						if (i == 0) {
							str += '<li role="presentation" class="active"><a href="#" data-leaf=' + data[i]['is_leaf'] + ' data-id=' + data[i]['id'] + '  data-depth=' + data[i]['depth'] + '>' + data[i]['name_zh_tw'];
							if (data[i]['is_leaf'] == 0) {
								str += '<span class="glyphicon glyphicon-chevron-right pull-right"></span>';
							}
							str += '</a></li>';

						} else {
							str += '<li role="presentation"><a href="#" data-leaf=' + data[i]['is_leaf'] + ' data-id=' + data[i]['id'] + '  data-depth=' + data[i]['depth'] + '>' + data[i]['name_zh_tw'];
							if (data[i]['is_leaf'] == 0) {
								str += '<span class="glyphicon glyphicon-chevron-right pull-right"></span>';
							}
							str += '</a></li>';
						}
					}
					str += '</ul><ul class="nav nav-pills nav-stacked  col-md-4 col-xs-4 category category_2 hide"></ul><ul class="nav nav-pills nav-stacked  col-md-4 col-xs-4 category category_3 hide"></ul></div></div>';
					str += '</div>';
					str += '<div class="modal-footer" style="background-color:white">';
					str += '<button type="button" class="btn btn-primary ensogo_product_category_ensure" data-dismiss="modal" disabled>确定</button>';
					str += '<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>';
					str += '</div>';
					str += '</div>';
					str += '</div>';
					str += '</div>';
					str += '</div>';
					str += '</div>';
					$('.panel-body').append(str);
					$('#Category_modal').modal('show');
					// console.log(($(window).width()-$('#Category_modal .modal-content').width())*0.5);
					// console.log(($(window).height()-$('#Category_modal .modal-content').height())*0.5);
					// $('#Category_modal .modal-content').offset({left:($(window).width()-$('#Category_modal .modal-content').width())*0.5 ,right:($(window).width()-$('#Category_modal .modal-content').width())*0.5, top:($(window).height()-$('#Category_modal .modal-content').height())*0.5,bottom:($(window).height()-$('#Category_modal .modal-content').height())*0.5});
				}

			}).done(function() {
				// if($('.category_2').html()==''){
				// 	parentCategoryId = $('.category_1 .active a').data('id');
				// 	// console.log(parentCategoryId);
				// 	getParentCategoryList(parentCategoryId,1);
				// }	
			});
		});

		// console.log($('ul.category li[role="presentation"]').size());
		$('body').on('click', 'ul.category li[role="presentation"]', function() {
			var parentCategoryId = $(this).find('a').data('id');
			var depth = $(this).find('a').data('depth');
			var is_leaf = $(this).find('a').data('leaf');
			$(this).parents('ul').find('li.active').removeClass('active');
			$(this).addClass('active');
			if (!is_leaf) {
				getParentCategoryList(parentCategoryId, depth + 1);
			} else {
				$('.category_' + (depth + 2)).addClass('hide');
			}
			// console.log(parentCategoryId);
		});

		$('body').on('click', '.ensogo_product_category_ensure', function() {

			var category_1 = $('.category_1 li.active').find('a').first().text();
			var category_2 = $('.category_2 li.active').find('a').first().text();
			var category_id = '';
			if($('.category_2 li.active').find('a').data('leaf')){
				category_id = $('.category_2 li.active').find('a').data('id');
				$('.category_3').html('');
			}
			var str = category_1 + ' > ' + category_2;
			if ($('.category_3').html() != '') {
				var category_3 = $('.category_3 li.active').find('a').first().text();
				category_id = $('.category_3 li.active').find('a').data('id');
				str += ' > ' + category_3;
			}
			$('.ensogo_product_category').attr('data-id', category_id);
			$('.ensogo_product_category').html(str);

		});

		$('#ensogo_product_name').blur(function() {
			var name = $(this).val();
			if (Ensogo.isContainChinese(name)) {
				Ensogo.tips({
					type: 'error',
					msg: '产品标题不能包含中文',
					existTime: 3000
				});
				return false;
			}
			if (name.length > 255) {
				Ensogo.tips({
					type: 'error',
					msg: '产品标题字数不能超过255个',
					existTime: 3000
				});
				return false;
			}
		});


		/*商品第一个变种信息填充*/
		$('#ensogo_product_parentSku').on('blur', function() {
			var $replace_sku = $(this).val();
			if($('input[name="sale_choose"]:checked').val() == 'single'){
				$('#goodsList tr input[name="sku"]').val($replace_sku);
				console.log($replace_sku);
			}else{
				$('#goodsList tr input[name="sku"]').each(function() {
					var variance_sku = $(this).val().split('-');
					var len = variance_sku.length;
					$(this).val($replace_sku + '-' + variance_sku[len-2] + '-' + variance_sku[len-1]);
				});
			}

		});

		$('#ensogo_product_price').on('change input', function() {
			if ($(this).val() != '') {
				if (parseFloat($(this).val()) == $(this).val().trim('0')) {
					if ($(this).val() <= 0) {
						$('#goodsList tr').find('input[name="price"]').val('');
						Ensogo.tips({
							type: 'error',
							msg: '商品售价必须大于0',
							existTime: 3000
						});
						return false;
					}
					$('#goodsList tr').find('input[name="price"]').val($(this).val());
				} else {
					Ensogo.tips({
						type: 'error',
						msg: '商品售价只能为数字',
						existTime: 3000
					});
					return false;
				}

			}
		});
		$('#ensogo_product_sale_price').on('change input', function() {
			// if($(this).val() != ''){
			// 	if(/^\d+$/.test($(this).val())){
			// 		$('#goodsList tr').eq(0).find('input[name="msrp"]').val($(this).val());
			// 	}else{
			// 		Ensogo.tips({type:'error',msg:'商品市场价只能为数字',existTime:3000});
			// 		return false;
			// 	}
			// }
			if (!(parseFloat($(this).val()) == $(this).val().trim('0')) && $(this).val()) {
				Ensogo.tips({
					type: 'error',
					msg: '商品市场价只能为数字',
					existTime: 3000
				});
				return false;
			}
			$('#goodsList tr').find('input[name="msrp"]').val($(this).val());
		});
		$('#ensogo_product_shipping').on('change input', function() {
			if ($(this).val() != '') {
				if (parseFloat($(this).val()) == $(this).val().trim('0')) {
					if ($(this).val() < 0) {
						$('#goodsList tr').find('input[name="shipping"]').val('');
						Ensogo.tips({
							type: 'error',
							msg: '商品运费不能为负',
							existTime: 3000
						});
						return false;
					}
					$('#goodsList tr').find('input[name="shipping"]').val($(this).val());
				} else {
					Ensogo.tips({
						type: 'error',
						msg: '商品运费只能为数字',
						existTime: 3000
					});
					return false;
				}
			}
		});
		$('#ensogo_product_count').on('change input', function() {
			if ($(this).val() != '') {
				if (parseFloat($(this).val()) == $(this).val().trim('0')) {
					if ($(this).val() <= 0) {
						$('#goodsList tr').find('input[name="inventory"]').val('');
						Ensogo.tips({
							type: 'error',
							msg: '商品库存必须大于0',
							existTime: 3000
						});
						return false;
					}
					$('#goodsList tr').find('input[name="inventory"]').val($(this).val());
				} else {
					Ensogo.tips({
						type: 'error',
						msg: '商品库存只能为数字',
						existTime: 3000
					});
					return false;
				}
			}
		});
		$('#ensogo_product_shipping_time input[name="shipping_short_time"]').on('change input', function() {
			if ($(this).val() != '') {
				if (parseFloat($(this).val()) == $(this).val().trim('0')) {
					$('#goodsList tr').find('input[name="shipping_short_time"]').val($(this).val());
				} else {
					Ensogo.tips({
						type: 'error',
						msg: '商品运输时间只能为数字',
						existTime: 3000
					});
					return false;
				}
			}
		});
		$('#ensogo_product_shipping_time input[name="shipping_long_time"]').on('change input', function() {
			if ($(this).val() != '') {
				if (parseFloat($(this).val()) == $(this).val().trim('0')) {
					$('#goodsList tr').find('input[name="shipping_long_time"]').val($(this).val());
				} else {
					Ensogo.tips({
						type: 'error',
						msg: '商品运输时间只能为数字',
						existTime: 3000
					});
					return false;
				}
			}
		});
		
		$('body').on('blur','#goodsList input[name="price"]',function(){
			if(($(this).val() <= 0) || (parseFloat($(this).val()) != $(this).val().trim('0'))){
				$(this).val('');
			}else{
				var price = parseFloat($(this).val()).toFixed(2);
				price == 0 ? $(this).val('') : $(this).val(price);
			}
		});

		$('body').on('blur','#goodsList input[name="msrp"],#goodsList input[name="shipping"]',function(){
			if(($(this).val() < 0) || (parseFloat($(this).val()) != $(this).val().trim('0'))){
				$(this).val('');
			}else{
				var msrp = parseFloat($(this).val()).toFixed(2);
				$(this).val(msrp);
			}
		});

		function getParentCategoryList(parentCategoryId, depth) {
			var parentCategoryId = parentCategoryId;
			var site_id = $('select[name="site_id"]').val();
			if (site_id == 0 || site_id == '') {
				Ensogo.tips({
					type: 'error',
					msg: '请选择店铺',
					existTime: 3000
				});
				return false;
			}
			if (parentCategoryId == 0) {
				Ensogo.tips({
					type: 'error',
					msg: '目录参数错误!',
					existTime: 3000
				});
				return false;
			}
			$.ajax({
				type: 'post',
				data: 'parentCategoryId=' + parentCategoryId + '&site_id=' + site_id,
				url: 'get-parent-category-id',
				success: function(data) {
					// console.log(data);
					console.log(data);
					var str = '';
					for (var i = 0; i < data.length; i++) {
						if (i == 0) {
							str += '<li role="presentation" class="active"><a href="#" data-leaf=' + data[i]['is_leaf'] + ' data-id=' + data[i]['id'] + '  data-depth=' + data[i]['depth'] + '>' + data[i]['name_zh_tw'];
							if (data[i]['is_leaf'] == 0) {
								str += '<span class="glyphicon glyphicon-chevron-right pull-right"></span>';
							}
							str += '</a></li>';
						} else {
							str += '<li role="presentation"><a href="#" data-leaf=' + data[i]['is_leaf'] + ' data-id=' + data[i]['id'] + '  data-depth=' + data[i]['depth'] + '>' + data[i]['name_zh_tw'];
							if (data[i]['is_leaf'] == 0) {
								str += '<span class="glyphicon glyphicon-chevron-right pull-right"></span>';
							}
							str += '</a></li>';
						}
					}
					$('.category_' + (depth + 1)).html('').append(str).removeClass('hide');
					// console.log($('.category_'+(depth + 1)+ ' li.active a').data('leaf'));
					if($('.category_'+(depth+ 1)+ ' li.active a').data('leaf')){
						$('.ensogo_product_category_ensure').removeAttr('disabled');
					}else{
						$('.ensogo_product_category_ensure').attr('disabled','disabled');
					}
					$('.category_' + (depth + 2)).addClass('hide');
				}
			});
			// }).done(function(){
			// if(depth<2){
			// 	// parentCategoryId = $('.category_'+(depth+1)+' .active a').data('id');
			// 	// getParentCategoryList(parentCategoryId,depth+1);
			// 	$('.category_'+(depth+1)+' .active').click();
			// }		

		}



		function colorRun() {
			for (var i = 0; i < goodsColorData.length; i++) {
				// $('#goodsColor').append('<div class="col-xs-2 mTop10 h50">
				// 	<label class="col-xs-12">
				// 	<span class="checkboxSpanCss fWhite ' + goodsColorData[i]["class"] + '"style="background:' + goodsColorData[i]["rgb"] + '">' + goodsColorData[i]["name"] + '</span>
				// 	<input type="checkbox" name="checkbox"  value="' + goodsColorData[i]["colorId"] + '">
				// 	</label></div>');
				$('#goodsColor').append('<div class="col-xs-2 mTop10 text-left" style="padding:0;"><input type="checkbox" name="checkbox" value="' + goodsColorData[i]["colorId"] + '"><span class="checkboxSpanCss ' + goodsColorData[i]['class'] + '" style="background:' + goodsColorData[i]["rgb"] + '"></span> <span>' + goodsColorData[i]["colorId"] + '</span></div>')
			}
		}


		function selColor(colorId) {
			if($.inArray(colorId,Ensogo.selColorArr) == '-1'){
				Ensogo.selColorArr.push(colorId);

			}
			var color = unescape(colorId);
			for (var k = 0; k < otherColorDataA.length; k++) {
				if (otherColorDataA[k].toLowerCase().replace(/ /g, "") == colorId.toLowerCase()) {
					color = otherColorDataA[k];
				}
			};
			colorId = "C" + unescape(colorId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
			var price = $('#ensogo_product_price').val();
			var msrp = $('#ensogo_product_sale_price').val();
			var shipping = $('#ensogo_product_shipping').val();
			var inventory = $('#ensogo_product_count').val();
			var parent_sku = $('#ensogo_product_parentSku').val();
			var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
			var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
			var rowspan = Ensogo.selSites.length;
			if (Ensogo.selSizeArr.length != 0) {
				for (var i = 0; i < Ensogo.selSizeArr.length; i++) {
					var size = unescape(Ensogo.selSizeArr[i]);
					var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
					var sku = createSku(parent_sku, color, size);
					$('tr[data-val="' + sizeId + '"]').remove();
					$('tr[uid="'+ sizeId +'"]').remove();
					var str = '<tr name="main"  id="' + colorId + '_' + sizeId + '" data-val="' + colorId + '_' + sizeId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'">' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '<input type="hidden" name="color" style="width:60px;" value="' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '"></td>';
					str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + unescape(size) + '<input type="hidden" name="size" style="width:60px;" value="' + unescape(size) + '"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
					str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
					for(var key in Ensogo.selSites){
						if(Ensogo.selSites.hasOwnProperty(key)){
							str += '<td class="site_area" style="text-align:center;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]] +'</td>';
							str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
							(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
							(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+ sku +'">');

						}		
					}	
					
					$('#goodsList').append(str);
				}
			} else {
				var sku = createSku(parent_sku, color, '');
				var str = '<tr name="main" id="' + colorId + '" data-val="' + colorId + '" data-sku="'+ sku +'" style="border-top:1px solid #CCC;">';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '<input type="hidden" name="color" style="width:60px;" value="' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '"></td>';
					str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="hidden" name="size" style="width:60px;" value=""></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
					str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;"  value="'+ inventory +'"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
					for(var key in Ensogo.selSites){
						if(Ensogo.selSites.hasOwnProperty(key)){
							str += '<td class="site_area" style="text-align:center;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]]+'</td>';
							str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
							(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
							(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId +'" data-sku="'+ sku +'">');
						}		
					}	
				$('#goodsList').append(str);
			}
		};

		function unSelColor(colorId) {
			Ensogo.arrDel(Ensogo.selColorArr, colorId);
			colorId = "C" + unescape(colorId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
			if (Ensogo.selSizeArr.length != 0) {
				for (var i = 0; i < Ensogo.selSizeArr.length; i++) {
					var size = unescape(Ensogo.selSizeArr[i]);
					var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
					//$('#'+ colorId.replace(/[\\']/g,"") + '_' + sizeId).remove();
					$('tr[data-val="' + colorId.replace(/[\\']/g, "") + '_' + sizeId + '"]').remove();
					$('tr[uid="'+ colorId.replace(/[\\']/g,"")+ '_' + sizeId + '"]').remove();
					Ensogo.ajustHeight('goods');
					if (Ensogo.selColorArr.length == 0) {
						var price = $('#ensogo_product_price').val();
						var msrp = $('#ensogo_product_sale_price').val();
						var shipping = $('#ensogo_product_shipping').val();
						var inventory = $('#ensogo_product_count').val();
						var parent_sku = $('#ensogo_product_parentSku').val();
						var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
						var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
						var rowspan = Ensogo.selSites.length;
						var sku = createSku(parent_sku, '', size);
						var str = '<tr name="main" id="' + sizeId + '" data-val="' + sizeId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="hidden" name="color" style="width:60px;" value=""></td>';
						str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">'+ unescape(size) +'<input type="hidden" name="size" style="width:60px;" value="'+ unescape(size) +'"></td>';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
						str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
						for(var key in Ensogo.selSites){
							if(Ensogo.selSites.hasOwnProperty(key)){
								str += '<td class="site_area" style="text-align:center;"  name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]]+'</td>';
								str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
								str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
								str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
								(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
								(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="'+ sizeId +'" data-sku="'+ sku +'">');
							}		
						}	
						$('#goodsList').append(str);
						Ensogo.ajustHeight('goods');
					}
				}
			} else {
				//$('#'+colorId.replace(/[\\']/g,"")).remove();
				$('tr[data-val="' + colorId.replace(/[\\']/g, "") + '"]').remove();
				$('tr[uid="'+ colorId.replace(/[\\']/g,"")+ '"]').remove();
				Ensogo.ajustHeight('goods');
			}
		}

		function selSize(sizeId) {
			if($.inArray(sizeId,Ensogo.selSizeArr) == '-1'){
				Ensogo.selSizeArr.push(sizeId);
			}
			var size = unescape(sizeId);
			var sizeId = "num" + unescape(sizeId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
			var price = $('#ensogo_product_price').val();
			var msrp = $('#ensogo_product_sale_price').val();
			var shipping = $('#ensogo_product_shipping').val();
			var inventory = $('#ensogo_product_count').val();
			var parent_sku = $('#ensogo_product_parentSku').val();
			var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
			var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
			var rowspan = Ensogo.selSites.length;
			if (Ensogo.selColorArr.length != 0) {
				for (var i = 0; i < Ensogo.selColorArr.length; i++) {
					var color = unescape(Ensogo.selColorArr[i]);
					var colorId = "C" + color.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
					var sku = createSku(parent_sku, color, size);
					$('tr[data-val="' + colorId + '"]').remove();
					$('tr[uid="'+ colorId +'"]').remove();
					var str = '<tr name="main" id="' + colorId + '_' + sizeId + '" data-val="' + colorId + '_' + sizeId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '<input type="hidden" name="color" style="width:60px;" value="' + color.replace(/(\w)/, function(v) {
						return v.toUpperCase()
					}) + '"></td>';
					str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + unescape(size) + '<input type="hidden" name="size" style="width:60px;" value="' + unescape(size) + '"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
					str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
					for(var key in Ensogo.selSites){
						if(Ensogo.selSites.hasOwnProperty(key)){
							str += '<td class="site_area" style="text-align:center;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]]+'</td>';
							str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
							(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
							(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+ sku +'">');
							
						}		
					}	
					
					$('#goodsList').append(str);
				}
			} else {
				var sku = createSku(parent_sku, '', size);
				var str = '<tr name="main" id="' + sizeId + '" data-val="' + sizeId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="hidden" name="color" style="width:60px;" value=""></td>';
					str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'">' + unescape(size) + '<input type="hidden" name="size" style="width:60px;" value="' + unescape(size) + '"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
					str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
					// console.log(Ensogo.sites);
					for(var key in Ensogo.selSites){
						if(Ensogo.selSites.hasOwnProperty(key)){
							str += '<td class="site_area" style="text-align:center;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]]+'</td>';
							str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
							str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
							(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
							(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="' + sizeId +'" data-sku="'+ sku +'">');
						}		
					}	
				$('#goodsList').append(str);
			}
		}

		//删除尺寸方法
		function unSelSize(size) {
			Ensogo.arrDel(Ensogo.selSizeArr, size);
			var sizeId = "num" + unescape(size).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
			if (Ensogo.selColorArr.length != 0) {
				var price = $('#ensogo_product_price').val();
				var msrp = $('#ensogo_product_sale_price').val();
				var shipping = $('#ensogo_product_shipping').val();
				var inventory = $('#ensogo_product_count').val();
				var parent_sku = $('#ensogo_product_parentSku').val();
				var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
				var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
				var rowspan = Ensogo.selSites.length;
				for (var i = 0; i < Ensogo.selColorArr.length; i++) {
					var color = unescape(Ensogo.selColorArr[i]);
					var colorId = "C" + color.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
					var id = colorId.replace(/[\\']/g, "") + '_' + sizeId;
					// $('#'+id).remove();
					$('tr[data-val="' + id + '"]').remove();
					$('tr[uid="'+ id +'"]').remove();
					Ensogo.ajustHeight('goods');
					if (Ensogo.selSizeArr.length == 0) {
						for (var k = 0; k < otherColorDataA.length; k++) {
							if (otherColorDataA[k].toLowerCase().replace(/ /g, "") == colorId.toLowerCase()) {
								color = otherColorDataA[k];
							}
						};
						var sku = createSku(parent_sku, color.replace(/(\w)/, function(v) {
							return v.toUpperCase()
						}), '');
						var str = '<tr name="main" id="' + colorId + '" data-val="' + colorId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'">' + color.replace(/(\w)/, function(v) { return v.toUpperCase()}) + '<input type="hidden" name="color" style="width:60px;" value="' + color.replace(/(\w)/, function(v) { return v.toUpperCase()}) + '"></td>';
						str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + unescape(size) + '<input type="hidden" name="size" style="width:60px;" value="' + unescape(size) + '"></td>';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
						str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
						for(var key in Ensogo.selSites){
							if(Ensogo.selSites.hasOwnProperty(key)){
								str += '<td class="site_area" style="text-align:center;" name="sites" data-site="'+ Ensogo.selSites[key] +'">'+ Ensogo.sites[Ensogo.selSites[key]]+'</td>';
								str += '<td class="site_area" style="text-align:center;"><input name="price" type="text" value="'+ price +'"></td>';
								str += '<td class="site_area" style="text-align:center;"><input name="msrp" type="text" value="'+ msrp +'"></td>';
								str += '<td class="site_area" style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ shipping +'"></td>';
								(key != 0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
								(key == Ensogo.selSites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId +'" data-sku="'+ sku +'">');
							}		
						}	
						$('#goodsList').append(str);
						Ensogo.ajustHeight('goods');
					};
				};
			} else {
				//$('#'+sizeId).remove();
				$('tr[data-val="' + sizeId + '"]').remove();
				$('tr[uid="'+ sizeId +'"]').remove();
				Ensogo.ajustHeight('goods');
			}
		}


		function createSku(parent_sku, color, size) {
			var sku = '';
			if (sku !== undefined) {
				sku = parent_sku;
			}
			if (color !== '' && color !== undefined) {
				// console.log(color);
				sku += '-' + color.replace(/(\w)/, function(v) {
					return v.toUpperCase()
				});
			}
			if (size !== '' && size !== undefined) {
				// console.log(size);
				sku += '-' + unescape(size);
			}
			return sku;
		};

		function createTag(that, iptOne) {
			if (iptOne.replace(/[\s]/g, '') != "") { //判断输入的是不是空值，不是空的就进行写入
				var iptTwo = iptOne.replace(/[.]/g, '');
				if (iptTwo != "") {
					Ensogo.memoryData.push(iptOne); //给数组memoryData写入input输入的值
					$(that).before('<span class="tag label label-info pull-left" style="margin-left:3px;margin-top:5px;"><span>' + iptOne + '</span><a href="javascript:void(0);" onclick="Ensogo.removeTag(this);" class="fWhite glyphicon glyphicon-remove glyphicon-tag-remove"></a></span>'); //写入统一格式的span
					$('#ensogo_product_tags').val(""); //清空输入框
				};
			};
			$('.tags_num').html($('#goods_tags span.tag').length);
		};
		//一键生成   obj = price inventory shipping 



	},
	AddVariance: function() {
		var str = '';
		str = '<tr>';
		str += "<td class='col-xs-1' style='text-align:center;'><input type='text' name='color' style='width:60px;'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='size' style='width:60px;'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='sku'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='price'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='msrp'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='inventory' style='width:80px;'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='shipping' style='width:80px;'></td>";
		str += "<td class='col-xs-1' style='text-align:center'><input type='text' name='shipping_short_time' style='width:40px;'> - <input type='text' name='shipping_long_time' style='width:40px;'></td><td>" + Ensogo.removeBtn + "</td>";
		$('#goodsList').append(str);
	},
	goodsRemove: function(ipt) {
		var id = $(ipt).closest('tr');
		var less = id.data('val');
		console.log(less);
		Ensogo.ajustHeight('goods');
		$('input[name="opt_method"]').val('all');
		//得到删除tr的colorId及sizeId
		var color = $(id).find('input[name="color"]').val();
		var size = $(id).find('input[name="size"]').val();
		$(id).remove();
		$('tr[uid="'+less+'"]').remove();

		var colorTd = $('#goodsList').find('input[name="color"]'),
			sizeTd = $('#goodsList').find('input[name="size"]'),
			colorTdArr = [],
			sizeTdArr = [];
		for (var i = 0; i < colorTd.length; i++) {
			// console.log($.inArray(escape(colorTd[i]),colorTdArr));
			if ($.inArray(escape($(colorTd[i]).val()), colorTdArr) == '-1') {
				colorTdArr.push(escape($(colorTd[i]).val()));
			}
		};
		for (var j = 0; j < sizeTd.length; j++) {
			// console.log($.inArray(escape(size[j]),sizeTdArr));
			if ($.inArray(escape($(sizeTd[j]).val()), sizeTdArr) == '-1') {
				sizeTdArr.push(escape($(sizeTd[j]).val()));
			}
		};
		//新色删除完以后数组及checkbox处理
		if ($.inArray(escape(color), colorTdArr) == '-1') {
			var colorId = escape(color.toLowerCase().replace(/ /g, ""));
			$('#goodsColor').find('input[type="checkbox"]').each(function() {
				if ($(this).val().toLowerCase() == colorId) {
					this.checked = false;
					Ensogo.arrDel(Ensogo.selColorArr, color);
				}
			});
		};
		if ($.inArray(escape(size), sizeTdArr) == '-1') {
			var sizeId = escape(size);
			$('#goodsSize').find('input[type="checkbox"]').each(function() {
				if ($(this).val() == sizeId) {
					this.checked = false;
					Ensogo.arrDel(Ensogo.selSizeArr, size);
				}
			});
		};
	},
	arrDel: function(arr, delVal) {
		for (var i = 0; i < arr.length; i++) {
			if (arr[i] == delVal) {
				arr.splice(i, 1);
				return arr;
			}
		}
	},
	ajustHeight: function(ags) {
		if (ags == 'color') {
			$count = $('#goodsColor input[name="checkbox"]').length;
			$row = Math.ceil($count / 6);
			$('#goodsColor').height($row * 25);
		} else if (ags == 'size') {
			$count = $('#goodsSize input[name="checkbox"]').length;
			$row = Math.ceil($count / 8);
			$('#goodsSize').height($row * 30);
		} else if (ags == 'goods') {
			$('.goodsList').height($('#goodsList').height() + $('.bgColor1').height() + 40);
		}
	},
	goto: function(str) {
		var winPos = $(window).scrollTop();
		var $ensogo_product_baseinfo = $('.ensogo_product_baseinfo').offset().top;
		var $ensogo_product_image = $('.ensogo_product_image').offset().top;
		var $ensogo_product_variance = $('.ensogo_product_variance').offset().top;
		Ensogo.isClick = true;
		$('html,body').animate({
			scrollTop: $('.' + str).offset().top
		}, 800, function() {
			Ensogo.isClick = false;
		});
		gotowhere = str;
		Ensogo.showscrollcss(str);
	},
	showscrollcss: function(str) {
		var eqtmp = new Array;
		eqtmp['ensogo_product_baseinfo'] = 0;
		eqtmp['ensogo_product_image'] = 1;
		eqtmp['ensogo_product_variance'] = 2;
		// console.log(eqtmp[str]);
		// console.log($('.left-panel p a').eq(eqtmp[str]).html());    
		$('.left-panel p a').css('color', '#333');
		$('.left-panel p a').eq(eqtmp[str]).css('color', '#FF9A00');
	},
	isContainChinese: function(str) {
		return /.*[\u4e00-\u9fa5]+.*/.test(str);
	},
	popup: function(args){
		$.overLay(1);
		var $content = '<div class="alert" role="alert" style="z-index:99999999;width:600px;height:300px;left:30%;right:30%;margin: auto;top:20%;position: fixed;background-color:#F9F9F9;">';
		var $tip = '<div style="text-align:center;font:bold 20px/50px Microsoft Yahei;color:#717171;margin:100px auto;">';
		if(args['type'] != 'success'){
			$tip += '<span class="iconfont icon-cuowu" style="font-size:50px;color:red;margin-right:20px;vertical-align:middle"></span>'+ args['msg'] +'</div>';
		}else{
			$tip +='<span class="iconfont icon-zhengque" style="font-size:50px;color:green;margin-right:20px;vertical-align:middle;"></span>'+ args['msg'] +'</div>';

		}
		$botton = '<div style="text-align:center;word-spacing:10px;position:absolute;bottom:10px;left:20%;right:20%;"><button type="button" class="btn btn-success" style="letter-spacing: 10px;padding: 5px 15px 5px 25px;margin-right:20px;font: bold 14px/20px Microsoft Yahei;" data-dismiss="alert" onclick="$.overLay(0)">确定</botton><button type="button" style="letter-spacing: 10px;padding: 5px 15px 5px 25px;color:#616161;background-color:#EFEFEF;font: bold 14px/20px Microsoft Yahei;" class="btn" data-dismiss="alert" onclick="$.overLay(0)">取消</botton></div>'
		$content += $tip + $botton;
		$content += '</div>';
		$('.alert').remove();
		$('.right_content').append($content);
	},
	poptip: function(args){
		var $content = '<div class="alert" role="alert" style="z-index:99999999;width:500px;height:230px;left:30%;right:30%;margin: auto;top:20%;position: fixed;background-color:#F9F9F9;"><button type="button" class="close" data-dismiss="alert">×</button>';
		var $tip = '<div style="text-align:center;font:bold 20px/50px Microsoft Yahei;color:#717171;margin:100px auto;">';
		if(args['type'] != 'success'){
			$tip += '<span class="iconfont icon-cuowu" style="font-size:50px;color:red;margin-right:20px;vertical-align:middle"></span>'+ args['msg'] +'</div>';
		}else{
			$tip +='<span class="iconfont icon-zhengque" style="font-size:50px;color:green;margin-right:20px;vertical-align:middle;"></span>'+ args['msg'] +'</div>';

		}
		$content += $tip;
		$content += '</div>';
		$('.alert').remove();
		$('.right_content').append($content);
	},
	tips: function(args) {
		var tips = args['type'];
		var tips_content = args['msg'];
		if (args['existTime'] != undefined) {
			var tips_time = args['existTime'];
		}
		// alert(tips_content);
		// alert(tips);
		if (tips == 'error') {
			$warning = '错误提醒:';
			$colorclass = 'alert-danger';
		} else {
			$warning = '温馨提示:';
			$colorclass = 'alert-success';
		}
		$content = ' <div class="alert ' + $colorclass + '" role="alert" style="z-index: 9999999; width: 680px; left: 30%; right: 30%; margin: auto; top: 8%; position: fixed;"><button type="button" class="close" data-dismiss="alert">×</button>';
		$tip = '<div class="pull-left mLeft10"><strong>' + $warning + '</strong></div>';
		$tip_content = '<div class="pull-left mLeft10"><span>' + tips_content + '</span></div>'
		$content += $tip + $tip_content;
		$('.alert').remove();
		$('.right_content').append($content);
		if (args['existTime'] != undefined) {
			setTimeout(function() {
				$('.alert').remove();
			}, tips_time);
		}
	},
	//删除清空的方法
	removeTag: function(obj) {
		var tag = $.trim($(obj).parent().text());
		var text = $(obj).parent().text();
		// 从数组中删除
		Ensogo.memoryData.splice($.inArray(tag, Ensogo.memoryData), 1);
		$(obj).parent().remove(); //移除当前焦点的父元素及子元素
		$('.tags_num').html($('#goods_tags span.tag').length);
	},
	initPhotosSetting: function() {
		$('div[role="image-uploader-container"]').batchImagesUploader({
			localImageUploadOn: true,
			fromOtherImageLibOn: true,
			imagesMaxNum: 11,
			fileMaxSize: 500,
			fileFilter: ["jpg", "jpeg", "gif", "pjpeg", "png"],
			maxHeight: 100,
			maxWidth: 100,
			initImages: Ensogo.existingImages,
			fileName: 'product_photo_file',
			onUploadFinish: function(imagesData, errorInfo) {

			},

			onDelete: function(data) {
				//			debugger
			}
		});
	},
	createNum: function(obj) {
		if ($('#goodsList tr').length == 0) {
			Ensogo.tips({
				type: 'error',
				msg: '请先创建变参商品',
				existTime: 3000
			});
			return false;
		}
		var str = "<div class='modal fade' id='create_modal'>";
		str += "<div class='modal-dialog'>";
		str += "<div class='modal-content' style='border:0;'>";
		str += "<div class='modal-header' style='background-color:#364655'>";
		str += "<button type='button' class='close' data-dismiss='modal' aria-label='Close'>";
		str += "<span aria-hidden='true'>&times;</span></button>";
		str += "<h4 class='modal-title' style='color:white;'>一键生成</h4>";
		str += "</div><div class='modal-body'>";
		var obj_name = ''
		if (obj == 'shipping_time') {
			obj_name = '运输时间'
			str += "<label for ='create_" + obj + "'>请输入" + obj_name + ": </label><input type='text'  name='create_shipping_short_time'> - <input type='text' name='create_shipping_long_time'>";
		} else {
			switch (obj) {
				case 'shipping':
					obj_name = '运费';
					break;
				case 'price':
					obj_name = '售价';
					break;
				case 'msrp':
					obj_name = '市场价';
					break;
				case 'inventory':
					obj_name = '库存';
					break;
			}
			str += "<label for='create_" + obj + "'>请输入" + obj_name + ": </label><input type='text' id='create_" + obj + "'>";
		}
		str += "</div><div class='modal-footer'><button type='button' class='btn btn-primary' data-dismiss='modal' onclick='Ensogo.ensureNum(\"" + obj + "\")'>确定</button></div></div></div>";
		$('.goodsList').after(str);
		$('#create_modal').modal('show');
	},
	ensureNum: function(obj) {
		console.log(obj);
		if (obj == 'shipping_time') {
			var shipping_short_time = $('input[name="create_shipping_short_time"]').val();
			var shipping_long_time = $('input[name="create_shipping_long_time"]').val();
			$('#goodsList tr').each(function() {
				$(this).find('input[name="shipping_short_time"]').val(shipping_short_time);
				$(this).find('input[name="shipping_long_time"]').val(shipping_long_time);
			});
		} else {
			$('#goodsList tr').each(function() {
				$(this).find('input[name="' + obj + '"]').val(parseFloat($('#create_' + obj).val()).toFixed(2));
			});
		}
	},
	checkSkuEnable: function(){
		var ensogo_id = $('input[name="ensogo_id"]').val();
		var site_id = $('select[name="site_id"]').val();
		var parent_sku = $('#ensogo_product_parentSku').val();
		var isExist = true;
		$.ajax({
			async: false,
			type:'get',
			data:{
				ensogo_id : (ensogo_id == undefined) ? '' : ensogo_id,
				site_id : site_id,
				parent_sku : parent_sku
			},
			url : '/listing/ensogo-offline/check-sku-enable',
			success:function(data){
				console.log(data);
				isExist = data;	

			}
		});
		return isExist;
	},
	checkVarianceSkuEnable: function(callback){
		var ensogo_id = $('input[name="ensogo_id"]').val();
		ensogo_id = (ensogo_id == undefined) ? '' : ensogo_id;
		var site_id = $('select[name="site_id"]').val();
		var parent_sku = $('#ensogo_product_parentSku').val();
		var isExist = true;
		var ExistVarianceSku = [];
		$('#goodsList input[name=sku]').each(function(){
			var variance_sku = $(this).val();
			if($.inArray(variance_sku,ExistVarianceSku) != '-1'){
				Ensogo.tips({type:'error',msg:'变种SKU不能重复,请修改!',existTime:3000});		
				isExist = false;
			}else{
				ExistVarianceSku.push(variance_sku);	
			}
		});
		if(isExist){
			$.ajax({
				async: false,
				type:'get',
				url:'/listing/ensogo-offline/check-variance-sku-enable',
				dataType: 'json',
				data:{
					ensogo_id : (ensogo_id == undefined) ? '' : ensogo_id,	
					site_id : site_id,
					parent_sku : parent_sku,
					variance_sku : ExistVarianceSku
				},
				success: function(data){
					console.log(data);
					isExist = data;
				}
			});
		}
		return isExist;
	},
	Save: function(obj) {
		var type = obj;
		var id = $('input[name="ensogo_id"]').val();
		var category = $('.ensogo_product_category').data('id');
		var name = $('#ensogo_product_name').val();
		var tags = [];
		$('#goods_tags .tag').each(function() {
			tags.push($(this).find('span').html());
		});
		var site_id = $('select[name="site_id"]').val();
		var tags = tags.join(',');
		var parent_sku = $('#ensogo_product_parentSku').val();
		var price = $('#ensogo_product_price').val();
		var msrp = $('#ensogo_product_sale_price').val();
		var shipping = $('#ensogo_product_shipping').val();
		var inventory = $('#ensogo_product_count').val();
		var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
		var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
		var shipping_time = shipping_short_time + '-' + shipping_long_time;
		var brand = $('#ensogo_product_brand').val();
		var upc = $('#ensogo_product_upc').val();
		var ladding_page_url = $('#ensogo_product_landding_page_url').val();
		var description = $('#ensogo_product_description').val();
		var main_image = '';
		var extra_image = [];
		var upindex = 1;
		var json_info = '{"category_info":"' + $(".ensogo_product_category").html() + '"}';
		var main_image_selected = 0;
	    $('#image-list .image-item a').each(function(){
	        if($(this).hasClass('select_photo')){
	            main_image_selected = 1;
	        }
	    });
	    if(main_image_selected){
	        for(var i =1 ; i<=11 ; i++){
	            if($('#image-list #image-item-'+i+' .thumbnail').hasClass('select_photo')  && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){
	                // console.log($('#image-list #image-item-'+i+' img').attr('src'));
	                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	            }else{
	                if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
	                    extra_image['extra_image_'+upindex] = '';
	                }else{
	                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	                }
	                upindex += 1;
	            }
	        }
	    }else{
	        for(var i=1;i<=11; i++){
	            if(i==1 && $('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png' ){
	                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	            }else{
	               if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
	                    extra_image['extra_image_'+upindex] = '';
	                }else{
	                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	                }
	                upindex += 1; 
	            }
	        }
	    }
		if (site_id == '' || site_id == undefined) {
			Ensogo.tips({
				type: "error",
				msg: '请选择要发布的店铺',
				existTime: 3000
			});
			return false;
		}
		if (category == undefined || category == '') {
			Ensogo.tips({
				type: 'error',
				msg: '请选择产品分类',
				existTime: 3000
			});
			return false;
		}
		if (name == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题不能为空',
				existTime: 3000
			});
			return false;
		} else if (Ensogo.isContainChinese(name)) {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题不能包含中文',
				existTime: 3000
			});
			return false;
		} else if (name.length > 255) {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题字数不能超过255个',
				existTime: 3000
			});
			return false;
		}
		if (tags == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品标签不能为空',
				existTime: 3000
			});
			return false;
		}
		if (parent_sku == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品主SKU不能为空',
				existTime: 3000
			});
			return false;
		}
		if (price == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品售价不能为空',
				existTime: 3000
			});
			return false;
		} else if (price <= 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品售价必须大于0',
				existTime: 3000
			});
			return false;
		}
		if ((msrp != '') && (Number(msrp) <= Number(price))) {
			Ensogo.tips({
				type: 'error',
				msg: '产品市场价必须大于售价',
				existTime: 3000
			});
			return false;
		}
		if (shipping == '' || shipping == undefined) {
			Ensogo.tips({
				type: 'error',
				msg: '产品运费不能为空',
				existTime: 3000
			});
			return false;
		} else if (shipping < 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品运费不能为负',
				existTime: 3000
			});
			return false;
		}
		if (inventory == '' || inventory == undefined) {
			Ensogo.tips({
				type: 'error',
				msg: '产品库存不能为空',
				existTime: 3000
			});
			return false;
		} else if (inventory <= 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品库存必须大于0',
				existTime: 3000
			});
			return false;
		}
		if (shipping_short_time == '' || shipping_long_time == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品运输时间不能为空',
				existTime: 3000
			});
			return false;
		}
		if ($('#ensogo_product_description').val() == '' || $('#ensogo_product_description').val() == undefined) {
			Ensogo.tips({
				type: 'error',
				msg: '产品描述不能为空',
				existTime: 3000
			});
			return false;
		}
		if (main_image == undefined || main_image == '') {
			Ensogo.tips({
				type: "error",
				msg: '请上传产品图片!',
				existTime: 3000
			});
			return false;
		}

		if($('#goodsList tr').length == 0){
				Ensogo.tips({type:"error",msg:'请先创建变参商品',existTime:3000});
				return false;
		}
		var ProductSkuEnable = Ensogo.checkSkuEnable();
		console.log('商品检测');
		if(!ProductSkuEnable){
			Ensogo.tips({type:'error',msg:'商品SKU已存在，请修改!'});
			return false;
		}

		var ProductVarianceSkuEnable = Ensogo.checkVarianceSkuEnable();
		console.log('变种检测');
		if(!ProductVarianceSkuEnable){
			Ensogo.tips({type:'error',msg:'变种SKU已存在，请修改！'});
			return false;
		}

		if(!Ensogo.checkSitesValid()){
			return false; 
		}
		var sale_style=  $('input[name="sale_choose"]:checked').val();
		var variance = (sale_style == 'single') ? function(){
			var sites  =  function(){
				var result = [];	
				$('#goodsList td[name="sites"]').each(function(){
					result.push($(this).data('site'));	
				});
				return result;
			}();
			var prices =  function(){
				var result = [];
				$('#goodsList input[name="price"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var msrps = function(){
				var result = [];
				$('#goodsList input[name="msrp"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var shippings = function(){
				var  result = [];
				$('#goodsList input[name="shipping"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var sku = $('#goodsList input[name="sku"]').val();
			var inventory = $('#goodsList input[name="inventory"]').val();
			($('#goodsList input[name="shipping_short_time"]').val() != '' && $('#goodsList input[name="shipping_long_time"]').val()!='') || function(){ Ensogo.tips({type:'error',msg:'变种运输时间不能为空',existTime:3000});return false}();
			var shipping_time = $('#goodsList input[name="shipping_short_time"]').val() + '-' + $('#goodsList input[name="shipping_long_time"]').val();
			var variances = function(sites,prices,msrps,shippings){
				var result = [];
				for(var i=0,len=sites.length;i<len; i++){
						if(result['countries'] == undefined) 
							result['countries'] = [] ;
						console.log(sites[i]);
						result['countries'].push(sites[i]) ;
						if(result['prices'] == undefined) 
							result['prices'] = [];
						result['prices'].push(prices[i]);
						if(result['msrps'] == undefined) 
							result['msrps'] = [];
						result['msrps'].push(msrps[i] || '0');
						if(result['shippings'] == undefined)
						 	result['shippings'] = [];
						result['shippings'].push(shippings[i]);
					// }
				}
				console.log(result);
				result.push({
					'countries': result['countries'].join('|'),
					'prices': result['prices'].join('|'),
					'msrps': result['msrps'].join('|'),
					'shippings': result['shippings'].join('|'),
					'sku': sku,	
					'inventory': inventory,
					'shipping_time': shipping_time,
					'enabled':'Y'
				});
				return result;
			}(sites,prices,msrps,shippings,sku,inventory,shipping_time);
			return variances;
		}() : function(){
			var variances = [];
			variances.push(function(){
				var result = [];
				$('#goodsList tr[name="main"]').each(function(){
					var _self = $(this);
					var variance = [];
					variance['color'] = _self.find('input[name="color"]').val();
					variance['size'] = _self.find('input[name="size"]').val();
					variance['sku'] = _self.find('input[name="sku"]').val();
					variance['inventory'] = _self.find('input[name="inventory"]').val();
					if(_self.find('input[name="shipping_short_time"]').val() == '' || _self.find('input[name="shipping_long_time"]').val() == ''){
						Ensogo.tips({type:'error',msg:'变种运输时间不能为空'});
						return false;
					}
					variance['shipping_time'] = _self.find('input[name="shipping_short_time"]').val() + '-' + _self.find('input[name="shipping_long_time"]').val();
					if(variance['countries'] == undefined)
						variance['countries'] = [];
					// variance['countries'].push(_self.find('td[name="sites"]').data('site'));
					if(variance['prices'] == undefined)
						variance['prices'] = [];
					// variance['prices'].push(_self.find('input[name="price"]').val());
					if(variance['msrps'] == undefined)
					 	variance['msrps'] = [];
					// variance['msrps'].push(_self.find('input[name="msrp"]').val() || '0');
					if(variance['shippings'] == undefined)
						variance['shippings'] = [];
					// variance['shippings'].push(_self.find('input[name="shipping"]').val());
					$('#goodsList tr[data-sku="'+ _self.data('sku') +'"]').each(function(){
						var price = $(this).find('input[name="price"]').val();	
						var site = $(this).find('td[name="sites"]').data('site');
						var msrp = $(this).find('input[name="msrp"]').val();
						var shipping = $(this).find('input[name="shipping"]').val();

						variance['countries'].push(site);	
						variance['prices'].push(price);
						variance['msrps'].push(msrp || '0');
						variance['shippings'].push(shipping);
					});
					result.push({
						'countries': variance['countries'].join('|'),
						'prices': variance['prices'].join('|'),
						'msrps': variance['msrps'].join('|'),
						'shippings': variance['shippings'].join('|'),
						'sku': variance['sku'],
						'inventory': variance['inventory'],
						'color': variance['color'],
						'size': variance['size'],
						'shipping_time': variance['shipping_time'],
						'enabled': 'Y'
					})	
				});
				return result;
			}());
			return variances[0];
		}();
		if(variance.length == 0){
			Ensogo.tips({type:'error',msg:'请先创建变种商品!',existTime:3000});
			return false;
		}
		var data = {
			'product_id': id == '' ? 0 : id,
			'category_id': category,
			'name': name,
			'tags': tags,
			'parent_sku': parent_sku,
			'prices': price,
			'msrps': msrp,
			'shippings': shipping,
			'shipping_time': shipping_time,
			'inventory': inventory,
			'brand': brand,
			'upc': upc,
			'ladding_page_url': ladding_page_url,
			'description': description,
			'enabled': 1,
			'json_info': json_info,
			'main_image': main_image,
			'extra_image_1': extra_image['extra_image_1'],
			'extra_image_2': extra_image['extra_image_2'],
			'extra_image_3': extra_image['extra_image_3'],
			'extra_image_4': extra_image['extra_image_4'],
			'extra_image_5': extra_image['extra_image_5'],
			'extra_image_6': extra_image['extra_image_6'],
			'extra_image_7': extra_image['extra_image_7'],
			'extra_image_8': extra_image['extra_image_8'],
			'extra_image_9': extra_image['extra_image_9'],
			'extra_image_10': extra_image['extra_image_10'],
			'sale_type': sale_style == 'single' ? '1': '2',
			'variants': variance
		};
		console.log(data);
		$.showLoading();
		$.ajax({
			type: 'post',
			url: 'save-and-post-product?type=' + type + '&site_id=' + site_id,
			data: data,
			success: function(data) {
				$.hideLoading();
				console.log(data);
				if (data['success'] == true) {
					if (type == 1) {
						Ensogo.tips({
							type: "success",
							msg: "保存成功",
							existTime: 3000
						});
					} else {
						Ensogo.tips({
							type: "success",
							msg: "发布成功,从在线商品中查看商品",
							existTime: 3000
						});
					}
					$.location.href('/listing/ensogo-offline/ensogo-post', 1500);
				} else {
					if (type == 1) {
						Ensogo.tips({
							type: "error",
							msg: "保存失败," + data['message'],
							existTime: 3000
						});
					} else {
						if (data['product_save'] == false) {
							Ensogo.tips({
								type: 'error',
								msg: '保存失败,' + data['message'],
								existTime: 3000
							});
						} else {
							Ensogo.tips({
								type: "error",
								msg: '发布失败,' + data['message'],
								existTime: 3000
							});
							$.location.href('/listing/ensogo-offline/ensogo-post', 1500);
						}
					}
				}

			}
		});
	},
	OnlineSave: function() {
		var id = $('input[name="ensogo_id"]').val();
		var enable = $('input[name="ensogo_enable"]').val();
		var isOnline = $('input[name="isOnline"]').val();
		var name = $('#ensogo_product_name').val();
		var tags = [];
		$('#goods_tags .tag').each(function() {
			tags.push($(this).find('span').html());
		});
		var tags = tags.join(',');
		var category = $('.ensogo_product_category').data('id');
		var price = $('#ensogo_product_price').val();
		var msrp = $('#ensogo_product_sale_price').val();
		var shipping = $('#ensogo_product_shipping').val();
		var inventory = $('#ensogo_product_count').val();
		var site_id = $('select[name="site_id"]').val();
		var shipping_short_time = $('#ensogo_product_shipping_time input[name="shipping_short_time"]').val();
		var shipping_long_time = $('#ensogo_product_shipping_time input[name="shipping_long_time"]').val();
		var shipping_time = shipping_short_time + '-' + shipping_long_time;
		var parent_sku = $('#ensogo_product_parentSku').val();
		var brand = $('#ensogo_product_brand').val();
		var upc = $('#ensogo_product_upc').val();
		var ladding_page_url = $('#ensogo_product_landding_page_url').val();
		var description = $('#ensogo_product_description').val();
		var main_image = '';
		var extra_image = [];
		var upindex = 1;
		var main_image_selected = 0;
	    $('#image-list .image-item a').each(function(){
	        if($(this).hasClass('select_photo')){
	            main_image_selected = 1;
	        }
	    });
	    if(main_image_selected){
	        for(var i =1 ; i<=11 ; i++){
	            if($('#image-list #image-item-'+i+' .thumbnail').hasClass('select_photo')  && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){
	                // console.log($('#image-list #image-item-'+i+' img').attr('src'));
	                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	            }else{
	                if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
	                    extra_image['extra_image_'+upindex] = '';
	                }else{
	                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	                }
	                upindex += 1;
	            }
	        }
	    }else{
	        for(var i=1;i<=11; i++){
	            if(i==1  && $('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png' ){
	                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	            }else{
	               if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
	                    extra_image['extra_image_'+upindex] = '';
	                }else{
	                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210/,'');
	                }
	                upindex += 1; 
	            }
	        }
	    }

		if (name == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题不能为空',
				existTime: 3000
			});
		} else if (Ensogo.isContainChinese(name)) {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题不能包含中文',
				existTime: 3000
			});
			return false;
		} else if (name.length > 255) {
			Ensogo.tips({
				type: 'error',
				msg: '产品标题字数不能超过255个',
				existTime: 3000
			});
			return false;
		}
		if (tags == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品标签不能为空',
				existTime: 3000
			});
			return false;
		}
		if (parent_sku == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品主SKU不能为空',
				existTime: 3000
			});
			return false;
		}
		if (price == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品售价不能为空',
				existTime: 3000
			});
			return false;
		} else if (price <= 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品售价必须大于0',
				existTime: 3000
			});
			return false;
		}
		// if ((msrp != '') && (Number(msrp) <= Number(price))) {
		// 	Ensogo.tips({
		// 		type: 'error',
		// 		msg: '产品市场价必须大于售价',
		// 		existTime: 3000
		// 	});
		// 	return false;
		// }
		if (shipping == '' || shipping == undefined) {
			Ensogo.tips({
				type: 'error',
				msg: '产品运费不能为空',
				existTime: 3000
			});
			return false;
		} else if (shipping < 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品运费不能为负',
				existTime: 3000
			});
			return false;
		}
		if (inventory == '' || inventory == undefined) {
			Ensogo.tips({
				type: 'error',
				msg: '产品库存不能为空',
				existTime: 3000
			});
			return false;
		} else if (inventory <= 0) {
			Ensogo.tips({
				type: 'error',
				msg: '产品库存必须大于0',
				existTime: 3000
			});
			return false;
		}
		if (shipping_short_time == '' || shipping_long_time == '') {
			Ensogo.tips({
				type: 'error',
				msg: '产品运输时间不能为空',
				existTime: 3000
			});
			return false;
		}
		if (main_image == undefined || main_image == '') {
			Ensogo.tips({
				type: "error",
				msg: '请上传产品图片!',
				existTime: 3000
			});
			return false;
		}

		var ProductSkuEnable = Ensogo.checkSkuEnable();
		if(!ProductSkuEnable){
			Ensogo.tips({type:'error',msg:'商品SKU已存在，请修改!',existTime:3000});
			return false;
		}

		var ProductVarianceSkuEnable = Ensogo.checkVarianceSkuEnable;
		console.log('变种检测');
		if(!ProductVarianceSkuEnable){
			Ensogo.tips({type:'error',msg:'变种SKU已存在，请修改！',existTime:3000});
			return false;
		}

		if(!Ensogo.checkSitesValid()){
			return false; 
		}
		var sale_style=  $('input[name="sale_choose"]:checked').val();
		var variance = (sale_style == 'single') ? function(){
			var sites  =  function(){
				var result = [];	
				$('#goodsList td[name="sites"]').each(function(){
					result.push($(this).data('site'));	
				});
				return result;
			}();
			var prices =  function(){
				var result = [];
				$('#goodsList input[name="price"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var msrps = function(){
				var result = [];
				$('#goodsList input[name="msrp"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var shippings = function(){
				var  result = [];
				$('#goodsList input[name="shipping"]').each(function(){
					result.push($(this).val());
				});
				return result;
			}();
			var enable = $('#goodsList input[name="enable"]').val();
			var sku = $('#goodsList input[name="sku"]').val();
			var inventory = $('#goodsList input[name="inventory"]').val();
			($('#goodsList input[name="shipping_short_time"]').val() != '' && $('#goodsList input[name="shipping_long_time"]').val()!='') || function(){ Ensogo.tips({type:'error',msg:'变种运输时间不能为空',existTime:3000});return false}();
			var shipping_time = $('#goodsList input[name="shipping_short_time"]').val() + '-' + $('#goodsList input[name="shipping_long_time"]').val();
			var variances = function(sites,prices,msrps,shippings){
				var result = [];
				for(var i=0,len=sites.length;i<len; i++){
						if(result['countries'] == undefined) 
							result['countries'] = [] ;
						console.log(sites[i]);
						result['countries'].push(sites[i]) ;
						if(result['prices'] == undefined) 
							result['prices'] = [];
						result['prices'].push(prices[i]);
						if(result['msrps'] == undefined) 
							result['msrps'] = [];
						result['msrps'].push(msrps[i] || '0');
						if(result['shippings'] == undefined)
						 	result['shippings'] = [];
						result['shippings'].push(shippings[i]);
					// }
				}
				// console.log(result);
				result.push({
					'countries': result['countries'].join('|'),
					'prices': result['prices'].join('|'),
					'msrps': result['msrps'].join('|'),
					'shippings': result['shippings'].join('|'),
					'sku': sku,	
					'inventory': inventory,
					'shipping_time': shipping_time,
					'enabled': enable
				});
				return result;
			}(sites,prices,msrps,shippings,sku,inventory,shipping_time);
			return variances;
		}() : function(){
			var variances = [];
			variances.push(function(){
				var result = [];
				$('#goodsList tr[name="main"]').each(function(){
					var _self = $(this);
					var variance = [];
					variance['color'] = _self.find('input[name="color"]').val();
					variance['size'] = _self.find('input[name="size"]').val();
					variance['sku'] = _self.find('input[name="sku"]').val();
					variance['inventory'] = _self.find('input[name="inventory"]').val();
					if(_self.find('input[name="shipping_short_time"]').val() == '' || _self.find('input[name="shipping_long_time"]').val() == ''){
						Ensogo.tips({type:'error',msg:'变种运输时间不能为空'});
						return false;
					}
					variance['enable'] = _self.find('input[name="enable"]').val() ;
					variance['shipping_time'] = _self.find('input[name="shipping_short_time"]').val() + '-' + _self.find('input[name="shipping_long_time"]').val();
					if(variance['countries'] == undefined)
						variance['countries'] = [];
					// variance['countries'].push(_self.find('td[name="sites"]').data('site'));
					if(variance['prices'] == undefined)
						variance['prices'] = [];
					// variance['prices'].push(_self.find('input[name="price"]').val());
					if(variance['msrps'] == undefined)
					 	variance['msrps'] = [];
					// variance['msrps'].push(_self.find('input[name="msrp"]').val() || '0');
					if(variance['shippings'] == undefined)
						variance['shippings'] = [];
					// variance['shippings'].push(_self.find('input[name="shipping"]').val());
					$('#goodsList tr[data-sku="'+ _self.data('sku') +'"]').each(function(){
						var price = $(this).find('input[name="price"]').val();	
						var site = $(this).find('td[name="sites"]').data('site');
						var msrp = $(this).find('input[name="msrp"]').val();
						var shipping = $(this).find('input[name="shipping"]').val();

						variance['countries'].push(site);	
						variance['prices'].push(price);
						variance['msrps'].push(msrp || '0');
						variance['shippings'].push(shipping);
					});
					result.push({
						'countries': variance['countries'].join('|'),
						'prices': variance['prices'].join('|'),
						'msrps': variance['msrps'].join('|'),
						'shippings': variance['shippings'].join('|'),
						'sku': variance['sku'],
						'inventory': variance['inventory'],
						'color': variance['color'],
						'size': variance['size'],
						'shipping_time': variance['shipping_time'],
						'enabled': variance['enable']
					})	

				});
				return result;
			}());
			return variances[0];
		}();
		$.showLoading();
		$.ajax({
			type: 'post',
			url: '/listing/ensogo-online/save-product?site_id=' + site_id,
			data: {
				'product_id': id == '' ? 0 : id,
				'category_id': category,
				'name': name,
				'tags': tags,
				'parent_sku': parent_sku,
				'prices': price,
				'msrp': msrp,
				'shipping': shipping,
				'shipping_time': shipping_time,
				'inventory': inventory,
				'brand': brand,
				'upc': upc,
				'ladding_page_url': ladding_page_url,
				'description': description,
				'enabled': enable,
				'main_image': main_image,
				'extra_image_1': extra_image['extra_image_1'],
				'extra_image_2': extra_image['extra_image_2'],
				'extra_image_3': extra_image['extra_image_3'],
				'extra_image_4': extra_image['extra_image_4'],
				'extra_image_5': extra_image['extra_image_5'],
				'extra_image_6': extra_image['extra_image_6'],
				'extra_image_7': extra_image['extra_image_7'],
				'extra_image_8': extra_image['extra_image_8'],
				'extra_image_9': extra_image['extra_image_9'],
				'extra_image_10': extra_image['extra_image_10'],
				'sale_type': sale_style == 'single' ? '1': '2',
				'variants': variance
			},
			success: function(data) {
				$.hideLoading();
				console.log(data);
				if (data['success'] == true) {
					if(isOnline == true){
						Ensogo.tips({
							type: "success",
							msg: "发布成功,从在线商品中查看商品",
							existTime: 3000
						});
						$.location.href('/listing/ensogo-online/online-product-list', 1500);
					}else{
						Ensogo.tips({
							type: "success",
							msg: "发布成功,从下架商品中查看商品",
							existTime: 3000
						});
						$.location.href('/listing/ensogo-online/offline-product-list', 1500);
					}
				} else {
					Ensogo.tips({
						type: "error",
						msg: "发布失败,"+ data['message'],
						existTime: 3000
					});

				}
			}
		});
	},
	checkSitesValid: function(){
		var result = true;
		$('#goodsList input[name="price"]').each(function(){
			if( ($(this).val() <= 0) ||(parseFloat($(this).val()) != $(this).val().trim('0'))){
				$(this).val('');
			} 
			if($(this).val() == '' || $(this).val() == null ){
				Ensogo.tips({type:'error',msg:'变种分站售价必须大于0',existTime:3000});
				result = false;	
			}
		});
		$('#goodsList input[name="msrp"]').each(function(){
			if( ($(this).val() != '') && (($(this).val() <0) || (parseFloat($(this).val()) != $(this).val().trim('0')))){
				$(this).val('');
				Ensogo.tips({type:'error',msg:'变种分站市场价必须不得小于0',existTime:3000});
				result = false;
			}
		});
		$('#goodsList input[name="shipping"]').each(function(){
			if(($(this).val() < 0) || (parseFloat($(this).val()) != $(this).val().trim('0'))){
				$(this).val('');
			}
			if($(this).val() == '' ||$(this).val() == null){ 
				Ensogo.tips({type:'error',msg:'变种分站运费不得小于0',existTime:3000});
				result = false;
			}
		})
		$('#goodsList input[name="inventory"]').each(function(){
			if(($(this).val() <= 0)	|| (parseFloat($(this).val()) != $(this).val().trim('0'))){
				$(this).val('');
			}
			if($(this).val() == '' || $(this).val() == null){
				Ensogo.tips({type:'error',msg:'变种库存不能为0',existTime:3000});
				result = false;
			}
		});
		return result;
	},
	fillVarianceData: function() {
		var colorArr = [];
		var sizeArr = [];
		Ensogo.sale_type != 2 || function(){
			$('input[name="sale_choose"][value="multi"]').click();
		}();
		$('#goodsColor input[name="checkbox"]').each(function() {
			colorArr.push($(this).val());
		});
		$('#goodsSize input[type="checkbox"]').each(function() {
			sizeArr.push($(this).val());
		});
		$.each(Ensogo.existingVarianceList, function(i, n) {
			// 如果两个sku相等就隐藏（因为是ensogo生成的）
			// if (this.sku === this.parent_sku) {
			// 	return true;
			// }
			if (this.color != '') {

				if ($.inArray(escape(this.color), colorArr) != '-1') {
					$('#goodsColor input[value="' + this.color + '"]').attr('checked', 'checked');
				} else {
					$('#goodsColor').append('<div class="col-xs-2 mTop10 text-left" style="padding:0;"><input type="checkbox" checked="true" name="checkbox" value="' + this.color + '"><span>' + this.color + '</span></div>');
					colorArr.push(this.color);
					Ensogo.selColorArr.push(this.color);
					Ensogo.ajustHeight('color');
				}
			}
			if (this.size != '') {
				if ($.inArray(escape(this.size), sizeArr) != '-1') {
					$('#goodsSize input[value="' + this.size + '"]').attr('checked', 'checked');
				} else {
					$('#goodsSize').append('<div class="col-xs-1 mTop10 minW110 text-left"><input type="checkbox" name="checkbox" checked="true" value="' + this.size + '"><span>' + this.size + ' </span></div>');
					sizeArr.push(this.size);
					Ensogo.selSizeArr.push(this.size);
					Ensogo.ajustHeight('size');
				}
			}
			var color = this.color;
			var size = this.size;
			var colorId = color.toLowerCase();
			var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
			var price = this.price;
			var sku = this.sku;
			var inventory = this.inventory;
			var shipping = this.shipping;
			var parent_sku = this.parent_sku;
			var msrp = this.msrp || '';
			var shipping_time = this.shipping_time.split('-');
			var shipping_short_time = shipping_time[0];
			var shipping_long_time = shipping_time[1];
			var rowspan = Ensogo.selSites.length;
			var sites = function(sites){
				var result = [];
				var all_sites = [];
				for(key in Ensogo.sites){
					if(Ensogo.sites.hasOwnProperty(key)){
						all_sites.push(key);
					}
				}
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						result[$.inArray(sites[key].country_code,all_sites)] = sites[key];	
					}
				}
				return result;
			}(this.sites);
			if ($.inArray(escape(size), Ensogo.selSizeArr) == '-1') {
				Ensogo.selSizeArr.push(escape(size));
			}
			// console.log(removeBtn);
			if (color != "") {
				for (var k = 0; k < Ensogo.otherColorDataB.length; k++) {
					if (Ensogo.otherColorDataB[k].toLowerCase().replace(/ /g, "") == color.toLowerCase().replace(/ /g, "")) {
						colorId = Ensogo.otherColorDataB[k];
					}
				}
				var colorId = "C" + unescape(colorId).replace(/(\w)/, function(v) {
					return v.toUpperCase()
				}).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
				if ($.inArray(escape(color), Ensogo.selColorArr) == '-1') {
					Ensogo.selColorArr.push(escape(color));
				}
				if (size == '') {
					tId = colorId;
				} else {
					tId = colorId + '_' + sizeId;
				}
				var str = '<tr name="main" id="' + tId + '" data-val="' + tId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + color.replace(/(\w)/, function(v) {return v.toUpperCase()}) + '<input type="hidden" name="color" style="width:60px;" value="' + color.replace(/(\w)/, function(v) {return v.toUpperCase()}) + '"></td>';
				str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan">' + unescape(size) + '<input type="hidden" name="size" style="width:60px;" value="' + unescape(size) + '"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
				str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
				var siteNum = 0;
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						sites[key].msrp = sites[key].msrp == 0 ? '' : sites[key].msrp;
						str += '<td style="text-align:center;" name="sites" data-site="'+ sites[key].country_code +'">'+ Ensogo.sites[sites[key].country_code] +'</td>';
						str += '<td style="text-align:center;"><input name="price" type="text" value="'+ sites[key].price +'"></td>';
						str += '<td style="text-align:center;"><input name="msrp" type="text" value="'+ sites[key].msrp +'"></td>';
						str += '<td style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ sites[key].shipping +'"></td>';
						(siteNum !=0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
						(key == sites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+ sku +'">');
						
					}		
					siteNum += 1;
				}	
				$('#goodsList').append(str);
			} else {
				var str = '';
				if (size != "") {
					var tId = "num" + unescape(sizeId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
					str += '<tr name="main" id="' + tId + '" data-val="' + tId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'"><input type="hidden" name="color" value=""></td>';
					str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'">' + unescape(size) + '<input type="hidden" name="size" value="' + unescape(size) + '"></td>';
				} else {
					str += '<tr name="main" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
					if(Ensogo.sale_type == 2){
						str += '<td style="text-align:center;" rowspan="'+ rowspan +'"></td>';
						str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'"></td>';
					}
				}
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '"></td>';
				str += '<td style="text-align:center" rowspan="'+ rowspan +'"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>'
				var siteNum = 0;
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						sites[key].msrp = sites[key].msrp == 0 ? '' : sites[key].msrp;
						str += '<td style="text-align:center;" name="sites" data-site="'+ sites[key].country_code +'">'+ Ensogo.sites[sites[key].country_code] +'</td>';
						str += '<td style="text-align:center;"><input name="price" type="text" value="'+ sites[key].price +'"></td>';
						str += '<td style="text-align:center;"><input name="msrp" type="text" value="'+ sites[key].msrp +'"></td>';
						str += '<td style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ sites[key].shipping +'"></td>';
						if(Ensogo.sale_type == 2){
							(siteNum !=0) || (str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan">' + Ensogo.removeBtn + '</td>');
						}
						if(size != ""){
							(key == sites.length-1)||(str += '</tr><tr name="less" uid="' + sizeId +'" data-sku="'+ sku +'">');
						}else{
						(key == sites.length-1)||(str += '</tr><tr name="less" data-sku="'+ sku +'">');
						}
					}		
					siteNum += 1;
				}	
				$('#goodsList').append(str);
			}
		});

	},
	fillOnlineVarianceData: function() {
		Ensogo.sale_type == 2 ? $('input[name="sale_choose"][value="multi"]').attr('checked','checked') : $('input[name="sale_choose"][value="single"]').attr('checked','checked');
		// $('.bgColor1').prepend('<th class="col-xs-1 header_title" style="text-align:center;line-height:30px;">颜色</th><th class="col-xs-1 header_title" style="text-align:center;line-height:30px;">尺寸</th>');
		$.each(Ensogo.existingVarianceList, function(i, n) {
			// 如果两个sku相等就隐藏（因为是ensogo生成的）
			// if (this.sku === this.parent_sku) {
			// 	return true;
			// }

			var color = this.color;
			var size = this.size;
			var colorId = color.toLowerCase();
			var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
			var price = this.price;
			var sku = this.sku;
			var inventory = this.inventory;
			var shipping = this.shipping;
			var parent_sku = this.parent_sku;
			var msrp = this.msrp == 0 ? '' : this.msrp;
			var shipping = this.shipping;
			var inventory = this.inventory;
			var parent_sku = this.parent_sku;
			var shipping_time = this.shipping_time.split('-');
			var shipping_short_time = shipping_time[0];
			var shipping_long_time = shipping_time[1];
			var enable = this.enable;
			var rowspan = Ensogo.selSites.length;
			var sites = function(sites){
				var result = [];
				var all_sites = [];
				for(key in Ensogo.sites){
					if(Ensogo.sites.hasOwnProperty(key)){
						all_sites.push(key);
					}
				}
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						result[$.inArray(sites[key].country_code,all_sites)] = sites[key];
					}
				}
				return result;
			}(this.sites);
			if (color != "") {
				for (var k = 0; k < Ensogo.otherColorDataB.length; k++) {
					if (Ensogo.otherColorDataB[k].toLowerCase().replace(/ /g, "") == color.toLowerCase().replace(/ /g, "")) {
						colorId = Ensogo.otherColorDataB[k];
					}
				}
				var colorId = "C" + unescape(colorId).replace(/(\w)/, function(v) {
					return v.toUpperCase()
				}).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "C";
				if (size == '') {
					tId = colorId;
				} else {
					tId = colorId + '_' + sizeId;
				}
				var str = '<tr  name="main" id="' + tId + '" data-val="' + tId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="color" value="'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'" style="width:60px;"></td>';
				str += '<td name="size" style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="size" value="' + unescape(size) + '" style="width:60px;"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '" disabled></td>';
				str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>';
				var siteNum = 0;
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						sites[key].msrp = sites[key].msrp == 0 ? '' : sites[key].msrp;
						str += '<td style="text-align:center;" name="sites" data-site="'+ sites[key].country_code +'">'+ Ensogo.sites[sites[key].country_code] +'</td>';
						str += '<td style="text-align:center;"><input name="price" type="text" value="'+ sites[key].price +'"></td>';
						str += '<td style="text-align:center;"><input name="msrp" type="text" value="'+ sites[key].msrp +'"></td>';
						str += '<td style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ sites[key].shipping +'"></td>';
						if(siteNum ==0){ 
							str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan"><input type="checkbox" name="enable" value="'+ enable +'" '; 
							console.log(enable);
							if(enable == 'Y') str +='checked="checked"'; 
							str += ' onclick="Ensogo.enable_status(this)"/>上架</td>';
						}
						(key == sites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+ sku +'">');
						
					}		
					siteNum += 1;
				}	
				$('#goodsList').append(str);
			} else {
				var str = '';
				if (size != "") {
					var tId = "num" + unescape(sizeId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
					str += '<tr name="main" id="' + tId + '" data-val="' + tId + '" data-sku="'+ sku +'"` style="border-top: 1px solid #CCC;">';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="color" value="" style="width:60px;"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="size" value="' + unescape(size) + '" style="width:60px;"></td>';
				} else {
					str += '<tr name="main" style="border-top: 1px solid #CCC;" data-sku="'+ sku +'">';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="color" value=""  style="width:60px;"></td>';
					str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="size" value=""  style="width:60px;"></td>';
				}
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '" disabled></td>';
				str += '<td style="text-align:center" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
				str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>';
				var siteNum = 0;
				for(var key in sites){
					if(sites.hasOwnProperty(key)){
						sites[key].msrp = sites[key].msrp == 0 ? '' : sites[key].msrp;
						console.log(sites[key].msrp);
						str += '<td style="text-align:center;" name="sites" data-site="'+ sites[key].country_code +'">'+ Ensogo.sites[sites[key].country_code] +'</td>';
						str += '<td style="text-align:center;"><input name="price" type="text" value="'+ sites[key].price +'"></td>';
						str += '<td style="text-align:center;"><input name="msrp" type="text" value="'+ sites[key].msrp +'"></td>';
						str += '<td style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ sites[key].shipping +'"></td>';
						if(siteNum ==0){ 
							str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan"><input type="checkbox" name="enable" value="'+ enable +'" '; 
							console.log(enable);
							if(enable == 'Y') str +='checked="checked"'; 
							str += ' onclick="Ensogo.enable_status(this)"/>上架</td>';
						}
						(key == sites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+sku+'">');
					}		
					siteNum += 1;
				}	
				$('#goodsList').append(str);
			}
		});


	},
	fillSitesData: function (){
		$.each(Ensogo.existingVarianceList,function(i,n){
			var color = this.color;
			var size = this.size;
			var colorId = color.toLowerCase();
			var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
			var price =  $('#ensogo_product_price').val();
			var sku = this.sku;
			var inventory = this.inventory;
			var shipping = $('#ensogo_product_shipping').val();
			var parent_sku = this.parent_sku;
			var msrp = $('#ensogo_product_sale_price').val() == 0 ? '' : $('#ensogo_product_sale_price').val();
			var shipping_time = this.shipping_time.split('-');
			var shipping_short_time = shipping_time[0];
			var shipping_long_time = shipping_time[1];
			var enable = this.enable;
			var rowspan = Ensogo.selSites.length;
			var sites = function(sites,selSites){
				var result = [];
				var all_sites = [];
				for(key in Ensogo.sites){
					if(Ensogo.sites.hasOwnProperty(key)){
						all_sites.push(key);
					}
				}
				var old_sites = [];
				for(s in sites){
					if(sites.hasOwnProperty(s)){
						old_sites.push(sites[s]['country_code']);
					}
				}
				for(var k in selSites){
					if(selSites.hasOwnProperty(k)){
						if($.inArray(selSites[k],old_sites) != -1){
							result [$.inArray(selSites[k],all_sites)] = sites[$.inArray(selSites[k],old_sites)];
						}else{
							result[$.inArray(selSites[k],all_sites)] = {
								country_code : selSites[k],
								msrp : msrp,
								price: price,
								shipping: shipping,
							}
						}
					}
				}
				return result;
			}(this.sites,Ensogo.selSites);	
			rowspan = this.sites.length;
			var str = '';
			if(Ensogo.sale_type == 1){
				color = $('#goodsList input[name="color"]').val() == undefined ? '' : $('#goodsList input[name="color"]').val();
				size = $('#goodsList input[name="size"]').val() == undefined ? '' : $('#goodsList input[name="size"]').val();

				str += '<tr name="main"  data-sku ="'+ sku +'" style="border-top: 1px solid #CCC;">';
			}else{
				var colorId = color.toLowerCase();
				var sizeId = "num" + size.replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
				var tId = "num" + unescape(sizeId).replace(/[\s\.\\'*]+/g, "_").replace(/[\\"]+/g, "-") + "num";
				str += '<tr name="main" id="' + tId + '" data-val="' + tId + '" data-sku="'+ sku +'" style="border-top: 1px solid #CCC;">';
				
			}
			str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="color" value="'+ color +'" style="width:60px;"></td>';
			str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="size" value="' + unescape(size) + '" style="width:60px;"></td>';
			str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="sku" value="' + sku + '" disabled></td>';
			str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="inventory" style="width:80px;" value="'+ inventory +'"></td>';
			str += '<td style="text-align:center;" rowspan="'+ rowspan +'" class="rowspan"><input type="text" name="shipping_short_time" style="width:40px;" value="'+ shipping_short_time +'"> - <input type="text" name="shipping_long_time" style="width:40px;" value="'+ shipping_long_time +'"></td>';
			var siteNum = 0;
			for(var key in sites){
				if(sites.hasOwnProperty(key)){
					str += '<td style="text-align:center;" name="sites" data-site="'+ sites[key].country_code +'">'+ Ensogo.sites[sites[key].country_code] +'</td>';
					str += '<td style="text-align:center;"><input name="price" type="text" value="'+ sites[key].price +'"></td>';
					str += '<td style="text-align:center;"><input name="msrp" type="text" value="'+ sites[key].msrp +'"></td>';
					str += '<td style="text-align:center;"><input name="shipping" style="width:80px;" value="'+ sites[key].shipping +'"></td>';
					if(siteNum ==0){ 
						str += '<td style="text-align:center;vertical-align:middle;" rowspan="'+ rowspan +'" class="rowspan"><input type="checkbox" name="enable" value="'+ enable +'" '; 
						console.log(enable);
						if(enable == 'Y') str +='checked="checked"'; 
						str += ' onclick="Ensogo.enable_status(this)"/>上架</td>';
					}
				(key == sites.length-1)||(str += '</tr><tr name="less" uid="'+ colorId + '_' + sizeId +'" data-sku="'+ sku +'"">');
					
				}		
				siteNum += 1;
			}	
			$('#goodsList').append(str);
		});
	},
	site_sort: function(a,b){
		var all_sites = [];
		for(key in Ensogo.sites){
			if(Ensogo.sites.hasOwnProperty(key)){
				all_sites.push(key);
			}
		}
		return $.inArray(a,all_sites)- $.inArray(b,all_sites);
	},
	ListInit: function() {

		$('select[name="site_id"]').change(function() {
			$('#Ensogo_site_search').submit();
		})


		//批量删除
		$('.batch_del').click(function() {
			var $delList = [];
			if ($('input[name="product_sku"]:checked').length == 0) {
				alert('请至少选中一件商品');
				return false;
			}
	
			var ajaxFn = [];
			$('input[name="product_sku"]:checked').each(function() {
				var $product_id = $(this).parent().data('id');
				var $site_id = $(this).parent().data('site_id');
				var $sku = $(this).parent().data('sku');
				ajaxFn.push(function() {
					return $.get('product-batch-del?parent_sku=' + $sku + '&site_id=' + $site_id + '&product_id=' + $product_id);
				});
			});
			var is_all_success = true;
			$.showLoading();
			$.asyncQueue(ajaxFn, function(idx, data) {
				if (data['success'] == false) {
					is_all_success = false;
				}
				if (idx == (ajaxFn.length - 1)) {
					$.hideLoading();
					if (is_all_success) {
						Ensogo.tips({
							type: 'success',
							msg: '产品批量删除成功',
							existTime: 3000
						});
					} else {
						Ensogo.tips({
							type: 'error',
							msg: '产品批量删除失败',
							existTime: 3000
						});
					}
					$.location.reload(2000);

				}
			});
			// $('input[name="product_sku"]:checked').each(function() {
			// 	$delList.push($(this).val());
			// });

			// $lb_status = $('input[name="lb_status"]').val();
			// $.showLoading();
			// for (var i = 0; i < $delList.length; i++) {
			// 	$.ajax({
			// 		type: "post",
			// 		url: "del-product?lb_status=" + $lb_status,
			// 		data: 'parent_sku=' + $delList[i],
			// 		success: function(data) {
			// 			$.hideLoading();
			// 			if (data['success'] == true) {
			// 				$('input[name="product_sku"][value="' + $delList[i] + '"]').parents('tr').remove();
			// 				Ensogo.tips({
			// 					type: "success",
			// 					msg: "产品删除成功!",
			// 					existTime: 3000
			// 				});
			// 			} else {
			// 				Ensogo.tips({
			// 					type: 'error',
			// 					msg: '产品删除失败!',
			// 					existTime: 3000
			// 				});
			// 			}
			// 		}
			// 	}).done(function(data) {
			// 		if (data['success'] == true) {
			// 			$.location.reload(2000);
			// 		}
			// 	});
			// }
		});

		//删除
		$('.del_product').click(function() {
			var $product_id = $(this).data('id');
			var $site_id = $(this).data('site_id');
			var $sku = $(this).data('sku');
			$.showLoading();
			$.ajax({
				type: 'post',
				data: 'parent_sku=' + $sku + '&site_id='+ $site_id + '&product_id='+ $product_id,
				url: 'del-product',
				success: function(data) {
					$.hideLoading();
					if (data['success'] == true) {
						Ensogo.tips({
							type: "success",
							msg: "产品删除成功!",
							existTime: 3000
						});
						$.location.reload(2000);
					} else {
						Ensogo.tips({
							type: "error",
							msg: '产品删除失败',
							existTime: 3000
						});
					}
				}
			});

		});


		$('input[name="chk_ensogo_fanben_all"]').click(function() {
			if ($(this).is(":checked")) {
				// $('input[name="fanben_id"]').attr("checked","true");
				$('input[name="product_sku"]').prop("checked", "true");
			} else {
				$('input[name="product_sku"]').removeAttr('checked');
			}
		});
		//批量发布
		$('.batch_post').click(function() {
			if ($('input[name="product_sku"]:checked').length == 0) {
				alert('请至少选中一件商品');
				return false;
			}

			var ajaxFn = [];
			$('input[name="product_sku"]:checked').each(function() {
				var $product_id = $(this).parent().data('id');
				var $site_id = $(this).parent().data('site_id');
				var $sku = $(this).parent().data('sku');
				// var $id = $(this).closest('tr').find('input[name="product_id"]').val(),
				// 	$site_id = $(this).closest('tr').find('input[name="Ensogo_product_site_id"]').val(),
				// 	$sku = $(this).val();
				ajaxFn.push(function() {
					return $.get('push-product?parent_sku=' + $sku + '&site_id=' + $site_id + '&product_id=' + $product_id);
				});
			});
			var is_all_success = true;
			$.showLoading();
			$.asyncQueue(ajaxFn, function(idx, data) {
				console.log(data);
				if (data['success'] == false) {
					is_all_success = false;
				}
				if (idx == (ajaxFn.length - 1)) {
					$.hideLoading();
					if (is_all_success) {
						Ensogo.tips({
							type: 'success',
							msg: '批量发布成功，从在线商品和刊登失败查看发布结果',
							existTime: 3000
						});
					} else {
						Ensogo.tips({
							type: 'error',
							msg: '批量发布成功，从在线商品和刊登失败查看发布结果',
							existTime: 3000
						});
					}
					$.location.reload(2000);

				}
			});
		});

		// 列表页点击发布按钮
		$('.post_product').click(function() {
			// var site_id = $(this).parents('tr').find('input[name="Ensogo_product_site_id"]').val();
			// var parent_sku = $(this).parents('tr').find('input[name="product_sku"]').val();
			// var product_id = $(this).parents('tr').find('input[name="product_id"]').val();
			// console.log(product_id);
			// console.log(site_id);
			var $product_id = $(this).data('id');
			var $site_id = $(this).data('site_id');
			var $sku = $(this).data('sku');
			$.showLoading();
			$.ajax({
				type: 'get',
				data: 'parent_sku=' + $sku + '&site_id=' + $site_id + '&product_id=' + $product_id,
				url: 'push-product',
				success: function(data) {
					$.hideLoading();
					console.log(data);
					if (data['success'] == true) {
						Ensogo.tips({
							type: 'success',
							msg: '发布成功,从在线商品中查看商品',
							existTime: 3000
						});
					} else {
						Ensogo.tips({
							type: 'error',
							msg: '发布失败,从刊登失败查看失败原因',
							existTime: 3000
						});

					}
					$.location.reload(2000);
				}
			});
		});
	},
	enable_status: function (obj){
		if($(obj).val() == 'Y'){
			$(obj).val('N');
		}else{
			$(obj).val('Y');
		}
	}

}

$.domReady(function($el) {
	var $document = this;

	var EnsogoListing = {};

	/**
	 * 批量上下架
	 * @param  {[type]} ){	} [description]
	 * @return {[type]}        [description]
	 */
	EnsogoListing.enable = function(enable, params) {
		var url = '/listing/ensogo-online/' + enable + '-info';
		return $.post(url, params);
	};

	$el("[data-enable]").on('click', function() {
		EnsogoListing.enable('enabled', $(this).getParams(['dataParams']).dataParams)
			.then(function() {
				$.location.reload();
			});
	});

	$el("[data-disable]").on('click', function() {
		EnsogoListing.enable('disable', $(this).getParams(['dataParams']).dataParams)
			.then(function() {
				$.location.reload();
			});
	});

	$el("#multi-xiajia").on('click', function() {
		// 到处所有选中项
		var ajaxFn = [],
			$this = $(this);
		console.log($this.data('enabled'))
		if($el('#main-table tbody input:checked').length == 0){
			Ensogo.poptip({type:'error',msg:'请至少选中一件商品'});
			return false;
		}
		$el("#main-table tbody input:checked").each(function() {
			var $button = $(this).closest('tr').find('td:last a:last'),
				params = $button.getParams(['dataParams']).dataParams;
			ajaxFn.push(function() {
				return EnsogoListing.enable($this.data('enabled'), params);
			});
		});

		$.showModal('处理中... <span>0</span>', $this.text()).then(function($m) {
			$.asyncQueue(ajaxFn, function(idx, response) {
				if (idx == ajaxFn.length - 1) {
					$m.find("span").text('操作完成');
					$m.close();
					$.location.reload();
				} else {
					$m.find(".modal-content span").text(idx + 1);
				}
			});
		});
	});

	$el("#multi-xiugai").on('click',function(){
		var $products = $('input[type="checkbox"][data-check ="products"]').sift(':checked-part,:checked-all');
		var $variances = $('input[type="checkbox"][check-name ="variance"]').sift(':checked-all');
		if($products.length == 0){
			Ensogo.popup({type:'error',msg:'请至少选中一件商品'});
			return false;
		}
		var site_id = $('select[name="site_id"]').val();
		console.log(site_id);
		var temp = document.createElement('form');
		temp.action = '/listing/ensogo-online/batch-edit?site_id='+site_id;
		temp.method = "post";
		temp.style.display = "none";
		var menu_type = $(this).data('menu');
		var opt = document.createElement('input');
		opt.type = 'hidden';
		opt.name = 'menu_type';
		opt.value= menu_type;
		temp.appendChild(opt);
		$products.each(function(){
			var opt = document.createElement("input");
			opt.type = 'hidden';
			opt.name = 'product[]';
			opt.value= $(this).attr('check-all').split('_')[1];
			temp.appendChild(opt);
		});
		$variances.each(function(){
			var opt = document.createElement("input");
			opt.type = 'hidden';
			opt.name = 'variance_'+ $(this).data('check').split('_')[1] +'[]';
			opt.value = $(this).data('sku');
			temp.appendChild(opt);
		});
		document.body.appendChild(temp);
		temp.submit();

	});

	$el('.radio-box input[type="radio"]').on('click',function(){
		var $type = $(this).data('checked');
		$el('.'+$type+'-modal-content .radio-box span').removeClass('radio-checked').addClass('radio');
		$el(this).closest('.radio-box').find('span').removeClass('radio').addClass('radio-checked');
		$(this).prop('checked',true);
		$el('.'+$type+'-modal-content .radio-input').attr('disabled','disabled').addClass('disabled');
		$el('.'+$type+'-modal-content .'+$(this).data('type')).removeAttr('disabled').removeClass('disabled');
	})

	$el('.nav li[role="presentation"]').on('click',function(){
		var $type= $(this).data('modify');
		$el('.nav li[role="presentation"]').removeClass('active');	
		$el(this).addClass('active');
		$el('.modify-modal').hide();
		$el('.'+$type+'-modal-content').show();
		$el('#modify_ensure').attr('disabled',true).css({'background-color':'#444444','border':'1px solid #444444'});
	})



	$el('#modify_ensure').on('click',function(){
		var modify = $el('.nav li.active').data('modify');
		switch(modify){
			case 'price':
				var modify_type = $el('input[name="price_modify_type"]:checked').data('type');
				dealModify(modify,modify_type);
				break;
			case 'msrp':
				var modify_type = $el('input[name="msrp_modify_type"]:checked').data('type');
				dealModify(modify,modify_type);
				break;
			case 'inventory':
				var modify_type = $el('input[name="inventory_modify_type"]:checked').data('type');
				dealInventory(modify,modify_type);
				break;
			case 'shipping':
				var modify_type = $el('input[name="shipping_modify_type"]:checked').data('type');
				dealModify(modify,modify_type);
				break;
			case 'shipping_time':
				var shipping_time = $el('input[name="shipping_time"]:checked').val();
				dealShippingTime(shipping_time);
				break;
			case 'site':
				var site = $el('.site_select').val();
				var modify_type = $el('input[name="site_modify_type"]:checked').data('type');
				dealSite(modify,modify_type);
		}
		$document.close();
	});

	$el('.money_unit_select').on('change',function(){
		if($(this).val() == "pri"){
			$(this).closest('.modify-modal').find('.money_unit').html('美元').parent().find('.radio-input').attr('placeholder','示例: 1.00'); 
		}else{
			$(this).closest('.modify-modal').find('.money_unit').html('&nbsp;&nbsp;&nbsp;&nbsp;%').parent().find('.radio-input').attr('placeholder','示例: 10');
		}
	});	

	$el('input[name="shipping_time"]').on('click',function(){
		var mod_type = $el(this).val();
		var IsRight = true;
		if(mod_type != 'other'){
			$el('.error_tip').html('');
			$el('#modify_ensure').removeAttr('disabled').css({'background-color':'#2ecc71','border':'1px solid #2ecc71'});
		}else{
			$el('#modify_ensure').attr('disabled',true).css({'background-color':'#444444','border':'1px solid #444444'});
		}

	});

	function locked(Is){
		Is == true ? $el('#modify_ensure').removeAttr('disabled').css({'background-color':'#2ecc71','border':'1px solid #2ecc71'}) : $el('#modify_ensure').attr('disabled',true).css({'background-color':'#444444','border':'1px solid #444444'});
	}

	function IsNumber(val){
		return retrun = (parseFloat(val) != val.trim('0')) ? false : true;
	}
	$el('.radio-input').on('input blur',function(){
		var r_input = $(this);
		var mod_val = $(this).val();
		var type = $el('.nav li.active').data('modify');
		var IsRight = true;
		if(type == 'shipping_time'){
			if($el('input[name="shipping_time"]:checked').val() == 'other'){
				$el('.shipping_time-modal-content .radio-input').each(function(){
					var val = $(this).val();
					if( parseFloat(val) != val.trim('0') || val <= 0 ){
						$(this).val('');
						locked(false);
						IsRight = false;
					}
				});
			}
		}else if(type == 'inventory'){
			var mul_type = $el('input[name="inventory_modify_type"]:checked').data('type');
			if(parseFloat(mod_val) != mod_val.trim('0')){
				locked(false);
				IsRight =false;
			}
			$('tr[name="main"]').each(function(){
				var inventory = $(this).find('td').eq(5).html();				
				if(mul_type == 'add'){
					var mod_inventory = Math.ceil(parseInt(inventory) + parseInt(mod_val));
				}else{
					var mod_inventory = parseInt(mod_val);
				}
				if( mod_inventory <= 0){
					locked(false);
					IsRight = false;
				}
			});
		}else if(type == 'site'){
			var mod_type = $el('.'+ type + '-modal-content .money_unit_select').val();
			var mul_type = $el('input[name="site_modify_type"]:checked').data('type');
			var site = $('.site_select').val();
			if(parseFloat(mod_val) != mod_val.trim('0')){
				locked(false);
				IsRight =false;
			}
			$('.site_info[data-site='+ site +']').each(function(){
				var site_info  = $(this).html().split('/');
				var price = site_info[0];
				var msrp = site_info[1];
				var shipping = site_info[2];
				if(mul_type == 'add'){
					var mod_price = mod_type == 'pri' ? (parseFloat(price) + parseFloat(mod_val)).toFixed(2) : (parseFloat(mod_val)* parseFloat(price) /100 + parseFloat(price)).toFixed(2);
				}else{
					var mod_price =  parseFloat(mod_val).toFixed(2);
				}
				if(mod_price <= 0){
					locked(false);
					IsRight = false;
				}
			});
		}else{

			var mod_type = $el('.'+ type + '-modal-content .money_unit_select').val();
			var mul_type = $el('input[name="'+ type +'_modify_type"]:checked').data('type');
			$('.site_info').each(function(){
				var site_info = $(this).html().split('/');
				var price = site_info[0];
				var msrp = site_info[1];
				var shipping = site_info[2];
				if(parseFloat(mod_val) != mod_val.trim('0')){
					locked(false);
					IsRight =false;
				}
				switch(type){
					case 'price':	
						if(mul_type == 'add'){
							var mod_price = mod_type == 'pri' ? (parseFloat(price) + parseFloat(mod_val)).toFixed(2) : (parseFloat(mod_val)* parseFloat(price) /100 + parseFloat(price)).toFixed(2);
						}else{
							var mod_price =  parseFloat(mod_val).toFixed(2);
						}
						if(mod_price <= 0){
							locked(false);
							IsRight = false;
						}
						break;
					case 'msrp': 
						if(mul_type == 'add'){
							var mod_price = mod_type == 'pri' ? (parseFloat(price) + parseFloat(mod_val)).toFixed(2) : (parseFloat(mod_val)* parseFloat(price) /100 + parseFloat(price)).toFixed(2);
						}else{
							var mod_price =  parseFloat(mod_val).toFixed(2);
						}
						var mod_msrp = parseFloat(mod_price/price *msrp).toFixed(2);
						if(mod_price <= 0 || mod_msrp < 0){
							locked(false);
							IsRight = false;
						}
						break;
					case 'shipping':
						if(mul_type == 'add'){
							var mod_shipping =  mod_type == 'pri' ? (parseFloat(shipping) + parseFloat(mod_val)).toFixed(2) : (parseFloat(mod_val)*parseFloat(shipping)/100 + parseFloat(shipping)).toFixed(2);
						}else{
							var mod_shipping = parseFloat(mod_val).toFixed(2);
						}
						if(mod_shipping  < 0){
							locked(false);
							IsRight = false;
						}
						break;
				}
			});
		}
		if(!IsRight){
			var msg = '';
			switch(type){
				case 'price':
					msg = '批量修改后，有商品不符合售价必须大于0的要求，请重新输入';
					break;
				case 'msrp':
					msg = '批量修改后，有商品不符合售价必须大于0的要求，请重新输入';
					break;
				case 'inventory':
					msg = '批量修改后，有商品不符合库存必须大于0的要求，请重新输入';						
					break;
				case 'shipping':
					msg = '批量修改后，有商品不符合运费不能小于0的要求，请重新输入';
					break;
				case 'shipping_time':
					msg = '批量修改后，有商品不符合运输时间不能小于0的要求，请重新输入';
					break;
				case 'site':
					msg = '批量修改后，有商品不符合站点售价必须大于0的要求，请重新输入';
					break;
			}
			console.log(msg);
			$el('.'+type+'-modal-content .error_tip').html(msg);
			return false;
		}else{
			$el('.error_tip').html('');
			locked(true);
		}


	});

	function dealInventory(mod,type){
		var $modify_val = $el('.'+mod+'-modal-content').find('input.'+ type).val();
		$('tr[name="main"]').each(function(){
			var inventory = $(this).find('td').eq(5).html();
			if(type == 'add'){
				inventory = Math.ceil(parseInt(inventory) + parseInt($modify_val));
				if(inventory > 0){
					$(this).find('td').eq(5).html(inventory);
				}else{
					Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合库存必须大于0的要求，请重新输入'});
				}
			}else{
				inventory = parseInt($modify_val);
				if(inventory >0){
					$(this).find('td').eq(5).html(inventory);
				}else{
					Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合库存必须大于0的要求，请重新输入'});
				}
			}

		});
	}

	function dealShippingTime(shipping_time){
		if(shipping_time == 'other'){
			var shipping_short_time =  $el('input[name="shipping_short_time"]').val();		
			var shipping_long_time = $el('input[name="shipping_long_time"]').val();
			console.log(shipping_short_time);
			console.log(shipping_long_time);
			console.log(parseInt(shipping_short_time) > parseInt(shipping_long_time));
			if(parseInt(shipping_short_time) > parseInt(shipping_long_time)){

				shipping_time = shipping_long_time + '-' + shipping_short_time;
			}else{
				shipping_time = shipping_short_time + '-' + shipping_long_time;
			}
		}
		$('tr[name="main"]').each(function(){
			$(this).find('td').eq(4).html(shipping_time);	
		});
	}

	function dealSite(mod,type){
		var $modify_val = $el('.'+ mod + '-modal-content').find('input.'+ type).val();
		var $modify_type = $el('.'+ mod + '-modal-content .money_unit_select').val();
		var site = $el('.'+ mod + '-modal-content .site_select').val();
		$('.site_info[data-site='+ site +']').each(function(){
				var site_info = $(this).html().split('/');
				var price = site_info[0];
				var msrp = site_info[1];
				var shipping = site_info[2];
				if(type == 'add'){
					if($modify_type == 'pri'){
						price = (parseFloat($modify_val) + parseFloat(price)).toFixed(2);	
					}else{
						price = (parseFloat($modify_val)* parseFloat(price)/100 + parseFloat(price)).toFixed(2);
					}	
					$(this).html(price+'/'+msrp+'/'+shipping);
				}else{
					$(this).html(parseFloat($modify_val).toFixed(2)+'/'+ msrp +'/'+ shipping);
				}
		});

	}

	function dealModify(mod,type){
		var $modify_val = $el('.'+ mod +'-modal-content').find('input.'+ type).val();
		var $modify_type = $el('.'+ mod +'-modal-content .money_unit_select').val();
		$('.site_info').each(function(){
			var site_info = $(this).html().split('/');	
			var price = site_info[0];
			var msrp = site_info[1];
			var shipping = site_info[2];
			console.log(type);
			if(type == 'add'){
				if($modify_type == 'pri'){
					switch(mod){
						case 'price':
							price = (parseFloat($modify_val) + parseFloat(price)).toFixed(2);
							(price <= 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合售价必须大于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
						case 'msrp':
							old_price = price;
							price = (parseFloat($modify_val) + parseFloat(price)).toFixed(2);
							msrp = parseFloat(price/old_price *msrp).toFixed(2);
							(msrp < 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合市场价不能小于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
						case 'shipping':
							shipping = (parseFloat(shipping) + parseFloat($modify_val)).toFixed(2);
							(shipping < 0 ) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合运费不能小于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
					}
				}else{
					switch(mod){
						case 'price':
							price = (parseFloat($modify_val)* parseFloat(price) /100 + parseFloat(price)).toFixed(2);
							(price <= 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合售价必须大于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
						case 'msrp':
							old_price = price;
							price = (parseFloat($modify_val)* parseFloat(price)/100 + parseFloat(price)).toFixed(2);
							msrp = parseFloat(price/old_price * msrp).toFixed(2);
							(price <= 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合售价必须大于0的要求，请重新输入'});
							(msrp < 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合市场价不能小于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
						case 'shipping':
							shipping = (parseFloat($modify_val)*parseFloat(shipping)/100 + parseFloat(shipping)).toFixed(2);
							(shipping < 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合运费不能小于0的要求，请重新输入'});
							$(this).html(price+'/'+msrp+'/'+shipping);
							break;
					}
				}
			}else{
				switch(mod){
					case 'msrp':
						msrp = parseFloat(parseFloat($modify_val)/price * msrp).toFixed(2);
						(msrp < 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合市场价不能小于0的要求，请重新输入'});
					case 'price':
						($modify_val <= 0)	&& Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合售价必须大于0的要求，请重新输入'});
						$(this).html(parseFloat($modify_val).toFixed(2)+'/'+ msrp +'/'+ shipping);
						break;
					case 'shipping':
						($modify_val < 0) && Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合运费不能小于0的要求，请重新输入'});
						$(this).html(price + '/' + msrp + '/' + parseFloat($modify_val).toFixed(2));
						break;
				}
			}
		});
	}

	$el('.form-ensure').click(function(){
		var site_id = $('#ensogo_product_table').data('site_id');
		var url = $(this).data('href');		
		var product = [];
		var variance = [];
		var IsRight = true;
		$('tr[name="main"]').each(function(){
			var	parent_sku = $(this).data('parentsku');
			var shipping_time = $(this).find('td').eq(4).html();
			var inventory = $(this).find('td').eq(5).html();
			var product_id = $(this).data('product_id');
			var sku = $(this).find('td').eq(3).html();
			var sites = [];
			if(inventory <= 0){
				Ensogo.popup({type:'error',msg:'商品的库存必须大于0'})
				return false;
			}
			$('.variance_'+sku+' .site_info').each(function(){
				site_info = $.trim($(this).html()).split('/');
				if(site_info[0] <= 0){
					Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合售价必须大于0的要求，请重新输入'});
					IsRight = false;
				}
				if(site_info[1] < 0){
					Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合市场价不能小于0的要求，请重新输入'});
					IsRight = false;
				}
				if(site_info[2] < 0){
					Ensogo.popup({type:'error',msg:'批量修改后，有商品不符合运费不能小于0的要求，请重新输入'});
					IsRight = false;
				}
				sites.push({
						'country_code': $(this).data('site'),
						'price': site_info[0],
						'msrp': site_info[1],
						'shipping': site_info[2]
				});
			});
			product.push({
				'product_id': product_id,
				'parent_sku': parent_sku
			});
			if(variance[product_id] == undefined){
				variance[product_id] = [];
			}
			variance[product_id].push({
				'sku': sku,
				'shipping_time': shipping_time,
				'inventory': inventory,
				'sites': sites
			});
		});
		if(!IsRight){
			return false;
		}
		for(var i in product){
			if(product.hasOwnProperty(i)){
				product[i]['variance'] = variance[product[i]['product_id']];
			}
		}
		var data = {
			'product': product
		}
		$.showLoading();
		$.ajax({
			type: 'post',
			dataType: 'json',
			url : '/listing/ensogo-online/batch-edit-save?site_id='+site_id,
			data: data,
			success: function(data){
				$.hideLoading();
				if(data['success'] == false){
					Ensogo.popup({type:'error',msg:data['message']});
				}else{
					Ensogo.poptip({type:'success',msg:data['message']});
					setTimeout(function(){
						window.location.href= url;
					},2000);
				}
			}
		});
	});


	// 同步商品模态框
	$el('#sync-btn').on('modal.ready', function(e, $modal, title) {
		var $body = $modal.find(".modal-content"),
			$selectSiteId = $modal.find("select[name=site_id]"),
			$startBtn = $body.find("#beginSync"),
			$closeBtn = $body.find("button.modal-close"),
			interval,
			BeginSync = function(site_id) {
				$startBtn.attr('disabled',true);
				$selectSiteId.attr('disabled', true);
				return $.post('/listing/ensogo-online/sync-product', {
					site_id: site_id
				}, function(res) {
					interval = setInterval(function() {
						showProgress(res.id);
					}, 500);
				});
			},
			showProgress = function(queue_id) {
				// 查询进度
				$.get('/listing/ensogo-online/sync-product-progress', {
					queue_id: queue_id
				}, function(res) {
					$modal.find("#progress").text(res.progress);
					if (res.status == 'C') { // 已经完成
						onComplete();
					}
				});
			},
			onComplete = function(){
				clearInterval(interval);
				interval = null;
				$startBtn.hide(),
				$closeBtn.show();
				$selectSiteId.removeAttr('disabled');
			};


		$modal.on('close', function() {
			clearInterval(interval);
			interval = null;
		});

		// 触发事件
		if ($selectSiteId.find('option').size() == 1) {
			BeginSync($selectSiteId.find('option').eq(0).attr('value'));
		} else {
			$selectSiteId.on('change',function(){
				$closeBtn.hide(),
				$startBtn.show().removeAttr('disabled');
			});
			$startBtn.on('click', function() {
				console.log($startBtn,'click');
				BeginSync($selectSiteId.val());
			});
		}
	});

	// 搬家

	var refreshData = function(url) {
		return $.ajax({
			method: 'get',
			url: url
		});
	};

	// 点击批量发布
	$el("#topush").pauseEvents('click', function(e) {
		return $.promise(function(resolve, reject) {
			var count = $("#store-move").find('tbody .checktd [data-check=product_id]:checked');
			if (!count.size()) {
				$.alertBox('请先选择产品', 'error');
				reject();
			} else {
				resolve();
			}
		});
	});

	$el("#select-move-store").on('change', function() {
		var site_id = $(this).val();
		location.href = $.location.query({
			site_id: site_id
		});
	});


	// 选择tag进行列表筛选
	$el("a.select-tags:not([target])").on('click', function() {

        var $a = $(this),
            site_id = $("#hide_site_id").val(),
            $input = $(this).find('input');
        window.location = '/listing/ensogo-offline/store-move?platform=wish&site_id='+site_id+'&tags_name=' + $input.val();
        /*
		var $a = $(this),
			$input = $(this).find('input'),
			data = $.data(document, 'tags') || [],
			status = !$input.is(":checked");
		// 设置checkbox状态
		$input.prop('checked',status);
		// 单选
		if (status) {
			$a.addClass('active');
			$(".select-tags").not(this).removeClass('active').find("input").prop('checked',false).trigger('change');
			$input.prop('checked',true);
			data = [$input.val()];
			// if (data.indexOf($input.val()) < 0) {
			// 	data.push($input.val());
			// }
		} else {
			$a.removeClass('active');
			data = [];
			// if (data.indexOf($input.val()) >= 0) {
			// 	data.remove($input.val());
			// }
		}
		$.data(document, 'tags', data);
		$("#store-move tbody")
			.find('tr')
			.hide()
			.find('.checktd input[name=product_id]')
			.prop('checked', false);
		if (data.length) {
			$("#store-move tbody")
				.find('tr')
				.filter(function() {
					var rs = true,
						tags = $(this).find('.data-tags').val().split(',');
					$.each(data, function(k, v) {
						if (tags.indexOf(v) < 0) {
							rs = false;
							return false;
						}
					});
					return rs;
				}).show();
		} else {
			$("#store-move tbody").find('tr').show();
		}

		$.checkAll('product_id', $document, function() {
			return !$(this).closest('tr').is(":hidden");
		});

		$("#store-move [check-all]").prop('checked', data.length).trigger('change')
        */
	});


	var url_get_log = 'save-wish-move-log',
		url_move = 'wish-product-move';


	// 提交，开始同步
	$el("#ensogo-move-confirm").on('submit', function(e) {
		e.preventDefault();
		var
			category_id = $el("input[name=category]").val(),
			store_id = $el("select[name=site_id]").val(),
			shipping_time = $el("input[name=inventory_1]").val() + '-' + $el("input[name=inventory_2]").val(),
			total = $("#store-move tr.active").size(),
			queue = [];
		if (!category_id) {
			$.alertBox('请选择类目', 'error');
			return false;
		}
		// console.log($document);
		$document.close();
		$.openModal('get-progress', {}, '发布结果', 'post').then(function($context) {
			// 获取log_id
			$.ajax({
				method: 'post',
				url: url_get_log,
				data: {
					// wish_product_id:product_id,
					category_id: category_id,
					shipping_time: shipping_time,
					store_id: store_id,
					total: total
				}
			}).success(function(rs) {
				// console.log(rs);
				// 遍历选中的数据
				$("#store-move tr.active").each(function() {
					var $tr = $(this),
						product_id = $tr.find("input[name=product_id]").val(),
						parent_sku = $tr.find("td[data-name=parent_sku]").text();
					queue.push(function() {
						return $.promise(function(resolve, reject) {
							$.ajax({
								method: 'post',
								data: {
									wish_product_id: product_id,
									category_id: category_id,
									log_id: rs.id,
									shipping_time: shipping_time,
									ensogo_site_id: store_id
								},
								url: url_move,
								success: function(response) {
									resolve(response, parent_sku);
								}
							});
						});
					});
				});
				var progress = $context.find("#move-progress progress").progress(total),
					$result = $context.find("#result-info"),
					$skuV = $("#sku-view"),
					$now = $("#now");
				$("#total").text(total);
				$.asyncQueue(queue, function(index, rs, parent_sku) {
					// 进度条
					progress.add();
					// 显示进度
					$now.text(index+1);
					if (!rs.success) {
						$skuV.text(parent_sku);
						// 显示错误信息
						if ( 'error_message' in rs && typeof rs.error_message === 'object') {
							rs.error_message = rs.error_message.join(';');
						}
						$result.append("<li>" + parent_sku + ": " + rs.error_message + "</li>");
					}
					if(index == total - 1){
						$context.find("input").show();
					}
				});

			});
		});

	});

	// 搬家 end -- 


});