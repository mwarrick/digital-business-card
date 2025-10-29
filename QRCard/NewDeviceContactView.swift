//
//  NewDeviceContactView.swift
//  ShareMyCard
//
//  SwiftUI wrapper to present CNContactViewController(forNewContact:)
//

import SwiftUI
import Contacts
import ContactsUI

struct NewDeviceContactView: UIViewControllerRepresentable {
    let contact: Contact
    @Environment(\.dismiss) private var dismiss
    
    func makeUIViewController(context: Context) -> UINavigationController {
        let cnContact = ContactExportHelper.makeCNContact(from: contact)
        let controller = CNContactViewController(forNewContact: cnContact)
        controller.contactStore = CNContactStore()
        controller.delegate = context.coordinator
        let nav = UINavigationController(rootViewController: controller)
        return nav
    }
    
    func updateUIViewController(_ uiViewController: UINavigationController, context: Context) {}
    
    func makeCoordinator() -> Coordinator { Coordinator(dismiss: dismiss) }
    
    class Coordinator: NSObject, CNContactViewControllerDelegate {
        let dismiss: DismissAction
        init(dismiss: DismissAction) { self.dismiss = dismiss }
        
        func contactViewController(_ viewController: CNContactViewController, didCompleteWith contact: CNContact?) {
            dismiss()
        }
    }
}


