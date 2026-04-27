$(document).ready(function () {
    const view = {
        addLogEntry: function (log, msgType) {
            $("#log").text(`Last Action: ${msgType} - ${new Date().toLocaleTimeString()}`);
        },
        displayLoadingMask: function (isVisible) {
            $("#guideText").text(isVisible ? "กำลังสแกน... กรุณารอครู่หนึ่ง" : "กรุณาวางพาสปอร์ตลงบนเครื่อง");
        },
        updateStatus: function (isOnline) {
            const $status = $("#status");
            if (isOnline) {
                $status.removeClass("offline").addClass("online").text("ONLINE");
            } else {
                $status.removeClass("online").addClass("offline").text("OFFLINE");
            }
        },
    };

    const myScan = new WebFxScan();
    wrapLib(myScan);

    const imageData = { imageCache: [], index: 0, total: 0 };
    const proxyImageHandler = {
        set: function (obj, prop, value) {
            if (prop === "newImage") {
                obj.imageCache.push(value);
                $("#guideText").hide();
                $("#passportImg").attr("src", value.base64).fadeIn();

                if (value.ocrText) {
                    const info = value.ocrText;
                    $("#resNo").text(info.PassportNo || info.DocumentNo || "-");
                    $("#resName").text(info.Name || `${info.Familyname || ""} ${info.Givenname || ""}` || "-");
                }
                return true;
            }
            return Reflect.set(...arguments);
        },
    };
    const proxyImageData = new Proxy(imageData, proxyImageHandler);

    async function initKioskMode() {
        try {
            await myScan.connect({ ip: "127.0.0.1", port: "17778" });
            view.updateStatus(true);

            await myScan.setAutoScanCallback({
                callback: (file, errCode) => {
                    view.displayLoadingMask(false);
                    if (errCode === 0) {
                        proxyImageData.newImage = file;
                    } else {
                        $("#log").text(`Error Code: ${errCode}`);
                    }
                },
            });

            await myScan.setBeforeAutoScanCallback({
                callback: () => view.displayLoadingMask(true),
            });

            await myScan.init();
            await myScan.setScanner({
                deviceName: "A62",
                source: "Camera",
                recognizeType: "passport",
                resolution: 300,
                autoScan: true,
            });

            await myScan.scan();
        } catch (e) {
            view.updateStatus(false);
            setTimeout(initKioskMode, 5000);
        }
    }

    initKioskMode();

    function wrapLib(instance) {
        Object.getOwnPropertyNames(Object.getPrototypeOf(instance))
            .filter((prop) => typeof instance[prop] === "function" && prop !== "constructor")
            .forEach((methodName) => wrapLibHandler(instance, methodName));
    }

    function wrapLibHandler(instance, methodName) {
        const originalMethod = instance[methodName];
        instance[methodName] = async function (...args) {
            const log = { API: methodName, args: serialize(args) };
            view.addLogEntry(JSON.stringify(log), "up");
            try {
                const result = await originalMethod.apply(this, args);
                view.addLogEntry(JSON.stringify({ API: methodName, return: serialize(result) }), "down");
                return result;
            } catch (e) {
                view.addLogEntry(JSON.stringify({ API: methodName, error: e.message }), "down");
                throw e;
            }
        };
    }

    function serialize(obj) {
        if (typeof obj === "function") return obj.toString();
        if (obj === null || typeof obj !== "object") return obj;
        return JSON.parse(JSON.stringify(obj));
    }
});
