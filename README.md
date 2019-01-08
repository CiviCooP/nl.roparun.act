# nl.roparun.act

This extension contains an api `act.get` for the ACT of Roparun.
The software of the ACT could retrieve the data from CiviCRM.

*Example return data*

```json
{
    "is_error": 0,
    "version": 3,
    "count": 1,
    "values": 
    [{
        "id": "5107",
        "name": "Team De Snelle Jelles",
        "teamnr": "353",
        "start_location": "Parijs",
        "average_speed": "12.3",
        "city": "Ede",
        "country": "Nederland",
        "website": "http://www.snellejelles.nl",
        "facebook": "",
        "instagram": "",
        "twitter": "",
        "phone_during_event": "0612345678",
        "team_members": [
            {
                "id": "154",
                "is_team_captain": "1",
                "display_name": "Dhr. Aad van der Neut",
                "phone": "078-6154652",
                "email": "aadvandeneut@example.nl",
                "address": "Wipmolen 56",
                "postal_code": "3352 XR",
                "city": "Papendrecht",
                "country": "Nederland",
                "role": "Chauffeur",
                "waarschuw_in_geval_van_nood": "",
                "telefoon_in_geval_van_nood": ""
            },
            {
                "id": "9181",
                "is_team_captain": "0",
                "display_name": "Jantje van Beton",
                "phone": "0623004667",
                "email": "jantjebeton@example.nl",
                "address": "Sterkerij 17",
                "postal_code": "6717 XR",
                "city": "Ede",
                "country": "Nederland",
                "role": "Chauffeur",
                "waarschuw_in_geval_van_nood": "Mama",
                "telefoon_in_geval_van_nood": "12344"
            },
            {
                "id": "9155",
                "is_team_captain": "0",
                "display_name": "Mevr. Betty Jansen.",
                "phone": "0348430686",
                "email": "bettyjansen@exmaple.com",
                "address": "Boterbloemweide 67",
                "postal_code": "3487",
                "city": "Amersfoort",
                "country": "Nederland",
                "role": "Loper",
                "waarschuw_in_geval_van_nood": "",
                "telefoon_in_geval_van_nood": ""
            }
        ]
      }
    ]}

```