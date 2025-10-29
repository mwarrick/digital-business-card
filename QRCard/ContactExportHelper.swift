//
//  ContactExportHelper.swift
//  ShareMyCard
//
//  Utilities to convert app Contact into CNMutableContact for device export
//

import Foundation
import Contacts

enum ContactExportHelper {
    static func makeCNContact(from contact: Contact) -> CNMutableContact {
        let cn = CNMutableContact()
        cn.givenName = contact.firstName
        cn.familyName = contact.lastName
        
        // Email
        if let email = contact.email, !email.isEmpty {
            let emailValue = CNLabeledValue(label: CNLabelWork, value: email as NSString)
            cn.emailAddresses = [emailValue]
        }
        
        // Phones
        var phones: [CNLabeledValue<CNPhoneNumber>] = []
        if let work = contact.phone, !work.isEmpty {
            phones.append(CNLabeledValue(label: CNLabelWork, value: CNPhoneNumber(stringValue: work)))
        }
        if let mobile = contact.mobilePhone, !mobile.isEmpty {
            phones.append(CNLabeledValue(label: CNLabelPhoneNumberMobile, value: CNPhoneNumber(stringValue: mobile)))
        }
        cn.phoneNumbers = phones
        
        // Organization
        if let org = contact.company, !org.isEmpty {
            cn.organizationName = org
        }
        if let title = contact.jobTitle, !title.isEmpty {
            cn.jobTitle = title
        }
        
        // Address
        if contact.address != nil || contact.city != nil || contact.state != nil || contact.zipCode != nil || contact.country != nil {
            let postal = CNMutablePostalAddress()
            postal.street = contact.address ?? ""
            postal.city = contact.city ?? ""
            postal.state = contact.state ?? ""
            postal.postalCode = contact.zipCode ?? ""
            postal.country = contact.country ?? ""
            let labeled: CNLabeledValue<CNPostalAddress> = CNLabeledValue(label: CNLabelWork, value: postal as CNPostalAddress)
            cn.postalAddresses = [labeled]
        }
        
        // URL
        if let website = contact.website, !website.isEmpty {
            let urlValue = CNLabeledValue(label: CNLabelURLAddressHomePage, value: website as NSString)
            cn.urlAddresses = [urlValue]
        }
        
        // Birthday (YYYY-MM-DD)
        if let bday = contact.birthdate, bday.count >= 10 {
            let parts = bday.prefix(10).split(separator: "-")
            if parts.count == 3, let y = Int(parts[0]), let m = Int(parts[1]), let d = Int(parts[2]) {
                var comps = DateComponents()
                comps.year = y
                comps.month = m
                comps.day = d
                cn.birthday = comps
            }
        }
        
        // Notes
        var notes: [String] = []
        if let n = contact.notes, !n.isEmpty { notes.append(n) }
        if let c = contact.commentsFromLead, !c.isEmpty { notes.append("Comments: \(c)") }
        if !notes.isEmpty { cn.note = notes.joined(separator: "\n\n") }
        
        return cn
    }
}


