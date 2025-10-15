//
//  ShareMyCardApp.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/9/25.
//

import SwiftUI

@main
struct ShareMyCardApp: App {
    init() {
        print("🚀 ShareMyCardApp: App initializing...")
    }
    
    var body: some Scene {
        WindowGroup {
            AuthenticationView()
                .onAppear {
                    print("🚀 ShareMyCardApp: WindowGroup appeared")
                }
        }
    }
}
