import Foundation
import UIKit

/// How this copy of the app was installed: from TestFlight, from the App
/// Store, or from Xcode. The binary is the same either way, so no build flag
/// can tell them apart; the receipt can. TestFlight's receipt is named
/// `sandboxReceipt`, the App Store's `receipt`, and a build run from Xcode
/// has no receipt file at all.
enum QuestifyFunctions {

    // MARK: - Questify.InstallChannel

    class InstallChannel: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let receipt = Bundle.main.appStoreReceiptURL else {
                return ["channel": "debug"]
            }

            if receipt.lastPathComponent == "sandboxReceipt" {
                return ["channel": "testflight"]
            }

            // A missing receipt on a device that should have one means the
            // app has not been through a store either: a simulator run.
            let installed = FileManager.default.fileExists(atPath: receipt.path)

            return ["channel": installed ? "appstore" : "debug"]
        }
    }

    // MARK: - Questify.AppBuild

    /// The build number of the installed binary (CFBundleVersion). The
    /// packager raises it after the PHP config was baked, so the config can be
    /// one behind what TestFlight shows.
    class AppBuild: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let value = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? ""

            return ["build": Int(value) ?? 0]
        }
    }

    // MARK: - Questify.OpenUrl

    /// Hands an address to iOS, so a custom scheme such as `itms-beta://` opens
    /// the TestFlight app instead of failing in a web view.
    class OpenUrl: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let string = parameters["url"] as? String, let url = URL(string: string) else {
                return ["opened": false]
            }

            DispatchQueue.main.async {
                UIApplication.shared.open(url)
            }

            return ["opened": true]
        }
    }
}
